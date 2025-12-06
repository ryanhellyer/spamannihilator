# Link Click Tracking System - Analysis

## Overview

Simple counter-based analytics system tracking:
- **Link clicks** - When users click protected redirect links (by slug)
- **Page views** - When users visit any page (by route)

**Approach**: Redis counters with periodic sync to MariaDB. Single total counter per slug/route (no date breakdowns).

---

## Architecture

```
Click/View → Redis Cache::increment() → Laravel Scheduler (every 15 min) → MariaDB
```

### Real-time Tracking
- Use `Cache::increment()` with Redis
- Keys: `analytics:click:{slug}` and `analytics:pageview:{route}`
- Fast, atomic operations (<1ms)

### Scheduled Sync
- Laravel Task Scheduler runs `analytics:sync` command every 15 minutes
- Reads Redis counters, calculates increments, bulk upserts to MariaDB
- Cron job: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`

---

## Database Schema

```sql
-- Link click totals (one row per slug)
CREATE TABLE link_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url_mapping_id BIGINT UNSIGNED NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    click_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mapping (url_mapping_id),
    FOREIGN KEY (url_mapping_id) REFERENCES url_mappings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Page view totals (one row per route)
CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route VARCHAR(255) NOT NULL UNIQUE,
    view_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_route (route)
) ENGINE=InnoDB;
```

---

## Implementation Components

1. **AnalyticsService**
   - `trackLinkClick(string $slug): void` - Increments Redis counter
   - `trackPageView(string $route): void` - Increments Redis counter

2. **Artisan Command: `analytics:sync`**
   - Scans Redis for `analytics:*` keys
   - Calculates increments (Redis value - DB value)
   - Bulk upserts to MariaDB
   - Optionally resets Redis counters

3. **Laravel Scheduler** (`app/Console/Kernel.php`)
   ```php
   protected function schedule(Schedule $schedule)
   {
       $schedule->command('analytics:sync')->everyFifteenMinutes();
   }
   ```

4. **Integration Points**
   - Link clicks: `RedirectController::checking()` method
   - Page views: Middleware or route-level tracking

---

## Benefits

- ✅ Simple (~200 lines of code)
- ✅ Fast (Redis INCR <1ms, handles 100k+ ops/sec)
- ✅ Uses existing Redis infrastructure
- ✅ Avoids DB load (no direct writes on every request)
- ✅ Small DB footprint (one row per slug/route)
- ✅ Occasional data loss acceptable (per requirements)

---

## Notes

- Redis persistence (AOF/RDB) recommended for durability
- Sync frequency configurable (default: 15 minutes)
- Can keep Redis counters for real-time stats or reset after sync
