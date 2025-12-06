# Link Click Tracking System - Implementation Plan

## Overview

This document outlines the code structure and implementation details for the link click tracking system described in `LINK_CLICK_TRACKING_ANALYSIS.md`.

---

## File Structure

```
app/
├── Console/
│   └── Commands/
│       └── SyncAnalyticsCommand.php
├── Models/
│   ├── LinkClick.php
│   └── PageView.php
└── Services/
    └── AnalyticsService.php

database/
└── migrations/
    ├── YYYY_MM_DD_HHMMSS_create_link_clicks_table.php
    └── YYYY_MM_DD_HHMMSS_create_page_views_table.php

bootstrap/
└── app.php (modify to add scheduling)
```

---

## Component Details

### 1. AnalyticsService (`app/Services/AnalyticsService.php`)

**Purpose**: Handles real-time tracking via Redis counters.

**Responsibilities**:
- Increment Redis counters for link clicks and page views
- Provide methods for retrieving current counts (optional, for real-time stats)

**Methods**:
```php
public function trackLinkClick(string $slug): void
public function trackPageView(string $route): void
public function getLinkClickCount(string $slug): int (optional)
public function getPageViewCount(string $route): int (optional)
```

**Implementation Notes**:
- Uses `Cache::increment()` with Redis
- Keys: `analytics:click:{slug}` and `analytics:pageview:{route}`
- Follows the same pattern as `UrlLookupService` (service injection, constants for prefixes)
- No return values needed for tracking methods (void)

**Example Usage**:
```php
$analyticsService->trackLinkClick('example-slug');
$analyticsService->trackPageView('home');
```

---

### 2. LinkClick Model (`app/Models/LinkClick.php`)

**Purpose**: Eloquent model for `link_clicks` table.

**Properties**:
- `id` (bigint, primary key)
- `url_mapping_id` (bigint, nullable, foreign key)
- `slug` (string, unique)
- `click_count` (unsigned int, default 0)
- `created_at`, `updated_at` (timestamps)

**Relationships**:
- `belongsTo(UrlMapping::class)` - optional relationship

**Fillable Fields**:
```php
protected $fillable = [
    'url_mapping_id',
    'slug',
    'click_count',
];
```

**Methods**:
- Standard Eloquent methods
- `incrementClickCount(int $amount = 1)` - helper for incrementing

---

### 3. PageView Model (`app/Models/PageView.php`)

**Purpose**: Eloquent model for `page_views` table.

**Properties**:
- `id` (bigint, primary key)
- `route` (string, unique)
- `view_count` (unsigned int, default 0)
- `created_at`, `updated_at` (timestamps)

**Fillable Fields**:
```php
protected $fillable = [
    'route',
    'view_count',
];
```

**Methods**:
- Standard Eloquent methods
- `incrementViewCount(int $amount = 1)` - helper for incrementing

---

### 4. SyncAnalyticsCommand (`app/Console/Commands/SyncAnalyticsCommand.php`)

**Purpose**: Artisan command to sync Redis counters to MariaDB.

**Command Signature**: `php artisan analytics:sync`

**Responsibilities**:
1. Scan Redis for all `analytics:*` keys
2. Parse keys to determine type (click vs pageview) and identifier (slug vs route)
3. Calculate increments (Redis value - current DB value)
4. Bulk upsert to MariaDB using `updateOrCreate()` or raw queries
5. Optionally reset Redis counters after successful sync (configurable)

**Options**:
- `--reset-counters` - Reset Redis counters after sync (default: false)
- `--dry-run` - Show what would be synced without actually syncing

**Implementation Approach**:
```php
// Pseudo-code structure
1. Get all Redis keys matching 'analytics:*'
2. Group by type (click/pageview)
3. For each key:
   - Extract identifier (slug/route)
   - Get Redis value
   - Get current DB value (or 0 if not exists)
   - Calculate increment = Redis value - DB value
4. Bulk upsert using DB::transaction()
5. If --reset-counters: Delete Redis keys
6. Log summary (counts synced, errors, etc.)
```

**Error Handling**:
- Wrap in try-catch
- Log errors but don't fail entire sync
- Continue processing remaining keys even if one fails
- Return appropriate exit codes

**Performance Considerations**:
- Use `Redis::scan()` for large key sets (not `keys()`)
- Batch DB operations (chunk inserts/updates)
- Use transactions for atomicity

---

### 5. Database Migrations

#### Migration: `create_link_clicks_table.php`

```php
Schema::create('link_clicks', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('url_mapping_id')->nullable();
    $table->string('slug', 255)->unique();
    $table->unsignedInteger('click_count')->default(0);
    $table->timestamps();
    
    $table->index('url_mapping_id');
    $table->foreign('url_mapping_id')
          ->references('id')
          ->on('url_mappings')
          ->onDelete('set null');
});
```

#### Migration: `create_page_views_table.php`

```php
Schema::create('page_views', function (Blueprint $table) {
    $table->id();
    $table->string('route', 255)->unique();
    $table->unsignedInteger('view_count')->default(0);
    $table->timestamps();
    
    $table->index('route');
});
```

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

---

## Integration Points

### 1. Link Click Tracking

**File**: `app/Http/Controllers/RedirectController.php`

**Method**: `checking(string $slug)`

**Integration**:
```php
public function checking(string $slug): View
{
    $url = $this->urlLookupService->getUrl($slug);

    if (!$url) {
        abort(404);
    }

    // Track link click
    $this->analyticsService->trackLinkClick($slug);

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

---

### 2. Page View Tracking

**Option A: Middleware Approach** (Recommended)

Create `app/Http/Middleware/TrackPageViews.php`:

```php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);
    
    // Only track successful GET requests
    if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
        $route = $request->route()?->getName() ?? $request->path();
        app(AnalyticsService::class)->trackPageView($route);
    }
    
    return $response;
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\TrackPageViews::class,
    ]);
})
```

**Option B: Route-Level Tracking**

Add tracking directly in route closures/controllers:
```php
Route::get('/', function () {
    app(AnalyticsService::class)->trackPageView('home');
    return view('home');
});
```

**Recommendation**: Use Option A (middleware) for automatic tracking of all routes.

---

## Implementation Steps

1. **Create Database Migrations**
   - Run `php artisan make:migration create_link_clicks_table`
   - Run `php artisan make:migration create_page_views_table`
   - Implement schema as documented above

2. **Create Models**
   - Create `app/Models/LinkClick.php`
   - Create `app/Models/PageView.php`
   - Define fillable fields and relationships

3. **Create AnalyticsService**
   - Create `app/Services/AnalyticsService.php`
   - Implement tracking methods using `Cache::increment()`
   - Add constants for Redis key prefixes

4. **Create Sync Command**
   - Run `php artisan make:command SyncAnalyticsCommand`
   - Implement Redis scanning and DB sync logic
   - Add command options (--reset-counters, --dry-run)
   - Add error handling and logging

5. **Configure Scheduling**
   - Modify `bootstrap/app.php` to add `withSchedule()` callback
   - Set frequency to every 15 minutes

6. **Integrate Tracking**
   - Update `RedirectController` to inject and use `AnalyticsService`
   - Create and register `TrackPageViews` middleware (if using middleware approach)

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
ANALYTICS_RESET_COUNTERS_AFTER_SYNC=false
```

### Config File (Optional)

Create `config/analytics.php`:
```php
return [
    'sync_frequency' => env('ANALYTICS_SYNC_FREQUENCY', 15),
    'reset_counters_after_sync' => env('ANALYTICS_RESET_COUNTERS_AFTER_SYNC', false),
    'redis_prefix' => 'analytics:',
];
```

---

## Error Handling & Edge Cases

1. **Redis Unavailable**: 
   - Tracking methods should fail gracefully (log error, continue)
   - Sync command should handle Redis connection errors

2. **Database Unavailable During Sync**:
   - Log error, don't reset Redis counters
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

# Dry run
php artisan analytics:sync --dry-run

# Sync and reset counters
php artisan analytics:sync --reset-counters

# Check Redis keys
redis-cli KEYS "analytics:*"

# Check Redis counter value
redis-cli GET "analytics:click:example-slug"

# View scheduled tasks
php artisan schedule:list
```

---

## Future Enhancements (Out of Scope)

- Date-based breakdowns (daily/hourly stats)
- Geographic tracking
- User agent tracking
- Referrer tracking
- Real-time dashboard
- API endpoints for retrieving stats
- Export functionality

---

## Notes

- This implementation follows Laravel 12 conventions
- Uses existing Redis infrastructure (no new dependencies)
- Minimal code footprint (~200-300 lines total)
- Designed for high-throughput scenarios (100k+ ops/sec)
- Accepts occasional data loss (Redis persistence recommended but not required)
