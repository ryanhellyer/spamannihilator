# Link Click Tracking System - Implementation Plan

## Overview

This document outlines the code structure and implementation details for a simplified path-based hit tracking system. All page views and link clicks are treated uniformly as "hits" on a path.

### Architecture Overview

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ▼
                          ┌──────────────────┐      ┌──────────┐
                          │ AnalyticsService │ ───▶ │  Redis   │ (temporary, volatile)
                          │  (trackHit())    │      │ counters │ (reset after sync)
                          └──────────────────┘      └──────────┘
                                    │
                                    │ (periodic sync + reset)
                                    ▼
                          ┌─────────────────────┐
                          │ SyncAnalyticsCommand│ (thin CLI wrapper)
                          └──────────┬──────────┘
                                     │ calls
                                     ▼
                          ┌──────────────────────┐      ┌──────────────┐
                          │ AnalyticsSyncService │ ───▶ │ PathCounter  │
                          │   (sync logic)      │      │    Model     │
                          └──────────────────────┘      └──────┬───────┘
                                     │                           │
                                     │ reads                     │ writes
                                     ▼                           ▼
                                 ┌──────────┐            ┌──────────────┐
                                 │  Redis   │            │   MariaDB    │
                                 │ counters │            │ (persistent) │
                                 └──────────┘            └──────────────┘
```

**Key Separation**:
- **AnalyticsService** → writes to **Redis** (during requests)
- **AnalyticsSyncService** → contains sync logic (reads Redis, writes via PathCounter to MariaDB)
- **PathCounter Model** → reads/writes **MariaDB** (persistent storage)
- **SyncAnalyticsCommand** → thin CLI wrapper that calls AnalyticsSyncService (for scheduling/manual execution)

---

## File Structure

```
app/
├── Console/
│   └── Commands/
│       └── SyncAnalyticsCommand.php
├── Models/
│   └── PathCounter.php
└── Services/
    ├── AnalyticsService.php
    └── AnalyticsSyncService.php

database/
└── migrations/
    └── YYYY_MM_DD_HHMMSS_create_path_counters_table.php

bootstrap/
└── app.php (modify to add scheduling)
```

---

## Component Details

### 1. AnalyticsService (`app/Services/AnalyticsService.php`)

**Purpose**: Handles real-time tracking via Redis counters for any path hit.

**Responsibilities**:
- **Writes to Redis only** - Increment Redis counters for path hits
- Provide method for retrieving current counts from Redis (optional, for real-time stats)
- **Does NOT interact with the database**

**Methods**:
```php
public function trackHit(string $path): void
public function getHitCount(string $path): int (optional)
```

**Implementation Notes**:
- Uses `Redis::incr()` directly (not Cache facade)
- Key format: `analytics:hit:{path}`
- Uses Redis directly because this is **data storage** (counters), not caching
- `UrlLookupService` uses Cache facade for caching (temporary, can be lost)
- `AnalyticsService` uses Redis directly for **temporary counter storage** (volatile, not persistent, not backed up)
- Redis counters are reset after each successful sync - they only accumulate between syncs
- Follows same pattern as `UrlLookupService` (service injection, constants for prefixes)
- No return values needed for tracking method (void)
- **Only interacts with Redis** - never touches MariaDB

**Example Usage**:
```php
$analyticsService->trackHit('/checking/example-slug');  // Increments Redis counter
$analyticsService->trackHit('/');
$analyticsService->trackHit('/privacy-policy/');
```

**Data Flow**: Request → AnalyticsService → Redis (increment counter via Redis::incr())

**Example Implementation**:
```php
use Illuminate\Support\Facades\Redis;

private const REDIS_PREFIX = 'analytics:hit:';

public function trackHit(string $path): void
{
    Redis::incr(self::REDIS_PREFIX . $path);
}
```

---

### 2. PathCounter Model (`app/Models/PathCounter.php`)

**Purpose**: Eloquent model for `path_counters` MariaDB table. Represents aggregated hit counters transferred from Redis.

**Responsibilities**:
- **Reads/writes to MariaDB only** - Stores persistent counter values synced from Redis
- Used by `SyncAnalyticsCommand` to persist counter values from Redis to database
- **Does NOT interact with Redis** - only handles database operations

**Properties**:
- `id` (bigint, primary key)
- `path` (string, unique) - The path/route being tracked
- `hit_count` (unsigned int, default 0) - Aggregated counter value
- `created_at`, `updated_at` (timestamps)

**Fillable Fields**:
```php
protected $fillable = [
    'path',
    'hit_count',
];
```

**Methods**:
- Standard Eloquent methods (`find()`, `updateOrCreate()`, etc.)
- Used by sync command to persist counter values from Redis to database

**Data Flow**: SyncAnalyticsCommand → PathCounter Model → MariaDB (persist counters)

---

## Separation of Concerns

**Key Distinction**:

- **AnalyticsService** = **Redis operations** (fast, temporary storage)
  - Called during requests to increment counters
  - Never touches the database
  - Redis is volatile (not persistent, not backed up) - counters accumulate between syncs only
  
- **AnalyticsSyncService** = **Sync logic** (business logic)
  - Contains the core sync algorithm
  - Reads counter values from Redis (via `Redis` facade)
  - Writes counter values to Database (via `PathCounter` model)
  - **Resets Redis counters after successful sync** (critical - Redis is volatile, not backed up)
  - Transfers aggregated counters from temporary storage to persistent storage
  - Can be called from anywhere (commands, jobs, controllers, etc.)

- **PathCounter Model** = **Database operations** (persistent storage)
  - Used by sync service to read/write counter records in database
  - Never touches Redis
  - Represents aggregated counters transferred from Redis

- **SyncAnalyticsCommand** = **CLI wrapper** (interface layer)
  - Thin wrapper that calls AnalyticsSyncService
  - Provides command-line interface for manual execution
  - Can be scheduled via Laravel's task scheduler
  - Handles CLI-specific concerns (output formatting, exit codes)

---

### 3. AnalyticsSyncService (`app/Services/AnalyticsSyncService.php`)

**Purpose**: Service containing the core sync logic to transfer Redis counters to MariaDB.

**Responsibilities**:
1. **Read from Redis**: Scan Redis for all `analytics:hit:*` keys (using `Redis` facade)
2. Extract path from each key
3. **Read from Database**: Get current DB counter value for each path (using `PathCounter` model)
4. Calculate increments (Redis counter value - current DB counter value)
5. **Write to Database**: Bulk upsert counter values to MariaDB using `PathCounter::updateOrCreate()`
6. Return summary of sync operation

**Methods**:
```php
public function sync(): array
// Returns array with summary: ['synced' => int, 'errors' => array, ...]
```

**Implementation Approach**:
```php
// Pseudo-code structure
use Illuminate\Support\Facades\Redis;

1. Get all Redis keys matching 'analytics:hit:*' (using Redis::scan() or Redis::keys())
2. For each key:
   - Extract path (remove 'analytics:hit:' prefix)
   - Get Redis counter value (using Redis::get())
   - Get current DB counter value (or 0 if not exists)
   - Calculate increment = Redis value - DB value
3. Bulk upsert using DB::transaction()
4. **After successful DB update**: Delete/reset Redis counters (Redis::del() or Redis::set() to 0)
   - This is critical because Redis is volatile (not persistent, not backed up)
   - Redis counters are temporary - only valid until next sync
5. Return summary (counts synced, errors, etc.)
```

**Important**: Redis counters **must be reset after successful sync** because:
- Redis is not configured for persistence (data can be lost on restart)
- Redis is not backed up
- Redis counters are temporary storage - they should only accumulate between syncs
- After sync, counters start fresh at 0 for the next period

**Error Handling**:
- Wrap in try-catch
- Log errors but don't fail entire sync
- Continue processing remaining keys even if one fails
- **Only reset Redis counters after successful DB transaction commit**
- If DB transaction fails, keep Redis counters intact for retry
- Return summary with error details

**Performance Considerations**:
- Use `Redis::scan()` for large key sets (not `keys()`)
- Batch DB operations (chunk inserts/updates)
- Use transactions for atomicity

**Benefits of Service Approach**:
- **Reusable**: Can be called directly from anywhere - commands, jobs, controllers, events, API endpoints, etc.
- **Testable**: Easy to unit test without command infrastructure
- **Flexible**: Can be triggered by events, queues, or other mechanisms
- **Separation of Concerns**: Business logic separate from CLI interface
- **No Command Required**: The service can be called directly - the command is just a convenience wrapper for CLI/scheduling

**Usage Examples**:
```php
// Direct service call (no command needed)
$syncService = app(AnalyticsSyncService::class);
$result = $syncService->sync();

// From a controller
public function syncAnalytics(AnalyticsSyncService $syncService)
{
    $result = $syncService->sync();
    return response()->json($result);
}

// From a queued job
public function handle(AnalyticsSyncService $syncService)
{
    $syncService->sync();
}

// From an event listener
public function handle(AnalyticsSyncService $syncService)
{
    $syncService->sync();
}
```

---

### 4. SyncAnalyticsCommand (`app/Console/Commands/SyncAnalyticsCommand.php`)

**Purpose**: Artisan command that calls AnalyticsSyncService for CLI execution and scheduling.

**Note**: While `AnalyticsSyncService` can be called directly from anywhere, this command provides:
- Manual CLI execution (`php artisan analytics:sync`)
- Clean scheduler integration
- CLI-specific features (formatted output, exit codes)

**Command Signature**: `php artisan analytics:sync`

**Responsibilities**:
- Call `AnalyticsSyncService::sync()`
- Format and output results to console
- Return appropriate exit codes

**Implementation Approach**:
```php
public function __construct(
    private AnalyticsSyncService $syncService
) {
    parent::__construct();
}

public function handle(): int
{
    $result = $this->syncService->sync();
    
    $this->info("Synced {$result['synced']} paths");
    if (!empty($result['errors'])) {
        $this->error("Errors: " . count($result['errors']));
        foreach ($result['errors'] as $error) {
            $this->warn("  - {$error}");
        }
    }
    
    return empty($result['errors']) ? 0 : 1;
}
```

**Benefits**:
- **Thin Wrapper**: Minimal code, delegates to service
- **CLI Interface**: Provides command-line options and formatted output
- **Schedulable**: Can be scheduled via Laravel's task scheduler
- **Manual Execution**: Can be run manually via `php artisan analytics:sync`

---

### 5. Database Migration

#### Migration: `create_path_counters_table.php`

```php
Schema::create('path_counters', function (Blueprint $table) {
    $table->id();
    $table->string('path', 255)->unique();
    $table->unsignedInteger('hit_count')->default(0);
    $table->timestamps();
    
    $table->index('path');
});
```

**Notes**:
- Single table stores aggregated counter values for all paths
- Path can be route name, URL path, or slug - whatever makes sense for your use case
- Simple schema with just path and aggregated counter value
- Counters are synced from Redis by the SyncAnalyticsCommand

---

### 6. Scheduling Configuration

**File**: `bootstrap/app.php`

**Modification**: Add scheduling method to the Application configuration.

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(...)
    ->withMiddleware(...)
    ->withExceptions(...)
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('analytics:sync')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground();
    })
    ->create();
```

**Notes**:
- `withoutOverlapping()` prevents concurrent runs
- `runInBackground()` doesn't block other scheduled tasks
- Frequency configurable via config file or env variable
- The command calls `AnalyticsSyncService` internally, keeping business logic separate from CLI concerns

---

## Integration Points

### 1. Middleware Approach (Recommended)

Create `app/Http/Middleware/TrackPathHits.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPathHits
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only track successful GET requests
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            // Use route name if available, otherwise use path
            $path = $request->route()?->getName() ?? $request->path();
            $this->analyticsService->trackHit($path);
        }
        
        return $response;
    }
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\TrackPathHits::class,
    ]);
})
```

**Benefits**:
- Automatically tracks all routes
- No need to modify individual controllers
- Consistent tracking across the application

---

### 2. Controller-Level Tracking (Alternative)

If you prefer explicit tracking in controllers:

**File**: `app/Http/Controllers/RedirectController.php`

```php
public function checking(string $slug): View
{
    $url = $this->urlLookupService->getUrl($slug);

    if (!$url) {
        abort(404);
    }

    // Track hit on this path
    $this->analyticsService->trackHit("/checking/{$slug}");

    return view('redirect', [
        'redirectUrl' => $url,
        'slug' => $slug,
    ]);
}
```

**Dependency Injection**:
```php
public function __construct(
    private UrlLookupService $urlLookupService,
    private AnalyticsService $analyticsService
) {
}
```

**Recommendation**: Use middleware approach for automatic tracking of all routes.

---

## Path Normalization

**Decision Point**: How to represent paths?

**Option A: Route Names** (Recommended)
- Use Laravel route names: `'home'`, `'checking'`, `'privacy-policy'`
- Pros: Clean, consistent, doesn't change with URL structure
- Cons: Requires route names to be defined

**Option B: URL Paths**
- Use actual URL paths: `'/'`, `'/checking/example-slug'`, `'/privacy-policy/'`
- Pros: Works automatically, no route name needed
- Cons: Includes dynamic segments (slugs), less normalized

**Option C: Normalized Paths**
- Use path patterns: `'/'`, `'/checking/{slug}'`, `'/privacy-policy/'`
- Pros: Groups similar paths together
- Cons: Requires custom normalization logic

**Recommendation**: Use Option A (route names) in middleware, fallback to Option B (URL paths) if route name not available.

---

## Implementation Steps

1. **Create Database Migration**
   - Run `php artisan make:migration create_path_counters_table`
   - Implement schema as documented above

2. **Create Model**
   - Create `app/Models/PathCounter.php`
   - Define fillable fields

3. **Create AnalyticsService**
   - Create `app/Services/AnalyticsService.php`
   - Implement `trackHit()` method using `Redis::incr()`
   - Add constants for Redis key prefix
   - Use `Illuminate\Support\Facades\Redis`

4. **Create AnalyticsSyncService**
   - Create `app/Services/AnalyticsSyncService.php`
   - Implement `sync()` method with Redis scanning and DB sync logic
   - Add error handling and return summary array
   - Inject PathCounter model as needed

5. **Create Sync Command**
   - Run `php artisan make:command SyncAnalyticsCommand`
   - Create thin wrapper that calls AnalyticsSyncService
   - Format output for CLI
   - Inject AnalyticsSyncService via constructor

5. **Configure Scheduling**
   - Modify `bootstrap/app.php` to add `withSchedule()` callback
   - Set frequency to every 15 minutes

6. **Create Middleware**
   - Create `app/Http/Middleware/TrackPathHits.php`
   - Register middleware in `bootstrap/app.php`

7. **Testing**
   - Test Redis counter increments
   - Test sync command manually
   - Test scheduled execution
   - Verify data persistence in MariaDB

8. **Deployment**
   - Run migrations: `php artisan migrate`
   - Ensure cron is configured: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
   - Monitor logs for sync command execution

---

## Configuration

### Environment Variables

Add to `.env` (optional, for flexibility):
```env
ANALYTICS_SYNC_FREQUENCY=15  # minutes
```

### Config File (Optional)

Create `config/analytics.php`:
```php
return [
    'sync_frequency' => env('ANALYTICS_SYNC_FREQUENCY', 15),
    'redis_prefix' => 'analytics:hit:',
];
```

---

## Error Handling & Edge Cases

1. **Redis Unavailable**: 
   - Tracking methods should fail gracefully (log error, continue)
   - Sync command should handle Redis connection errors

2. **Database Unavailable During Sync**:
   - Log error
   - **Do NOT reset Redis counters** (they'll be synced on next successful run)
   - Retry on next scheduled run

3. **Concurrent Syncs**:
   - Use `withoutOverlapping()` in scheduler
   - Consider using Redis locks for additional safety

4. **Large Key Sets**:
   - Use `Redis::scan()` instead of `keys()` to avoid blocking
   - Process in chunks/batches

5. **Counter Overflow**:
   - Use `unsignedInteger` (max ~4.2 billion)
   - Consider monitoring and alerting at high values

6. **Path Length**:
   - Database column is `VARCHAR(255)` - ensure paths fit
   - Consider truncation or hashing for very long paths

---

## Performance Considerations

1. **Redis Operations**: 
   - `INCR` is atomic and fast (<1ms)
   - No locking needed for increments

2. **Sync Performance**:
   - Batch DB operations (use `DB::transaction()`)
   - Consider chunking for very large datasets
   - Use `updateOrCreate()` for upserts (Laravel handles efficiently)

3. **Memory Usage**:
   - Redis keys are small (counter values)
   - Periodic sync prevents unbounded growth

4. **Middleware Overhead**:
   - Minimal - single Redis increment per request
   - Consider excluding certain paths (e.g., admin routes, API endpoints)

---

## Monitoring & Debugging

### Logging

Add logging to:
- Sync command: log counts synced, errors, duration
- AnalyticsService: log Redis failures (optional, can be noisy)

### Useful Commands

```bash
# Manual sync
php artisan analytics:sync

# Check Redis keys
redis-cli KEYS "analytics:hit:*"

# Check Redis counter value
redis-cli GET "analytics:hit:/checking/example-slug"

# View scheduled tasks
php artisan schedule:list
```

---

## Notes

- This implementation follows Laravel 12 conventions
- Uses existing Redis infrastructure (no new dependencies)
- Minimal code footprint (~150-200 lines total)
- Designed for high-throughput scenarios (100k+ ops/sec)
- Redis is volatile (not persistent, not backed up) - counters reset after each sync
- Data loss window: hits between syncs can be lost if Redis crashes/restarts
- Sync frequency (15 min default) balances data safety vs performance
- Simplified approach: single table, single model, single tracking method
- All hits (link clicks, page views) treated uniformly as path hits
- PathCounter model represents aggregated counters transferred from Redis to database
