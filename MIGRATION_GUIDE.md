# Production Database Migration Guide

## Pre-Migration Checklist

1. **Backup your database** (CRITICAL!)
   ```bash
   # Example for MariaDB/MySQL
   mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Check current migration status**
   ```bash
   php artisan migrate:status
   ```

3. **Review pending migrations**
   ```bash
   php artisan migrate --pretend
   ```
   This shows what would run without actually executing.

## Running Migrations in Production

### Option 1: Standard Migration (Recommended)
```bash
php artisan migrate
```

### Option 2: Force Run (if in maintenance mode)
```bash
php artisan migrate --force
```

### Option 3: Step-by-Step (for safety)
```bash
# Run one migration at a time
php artisan migrate --step
```

## Expected Migrations

Based on your codebase, these migrations should run:

1. **`2025_12_06_181929_add_hit_count_to_url_mappings_table.php`**
   - Adds `hit_count` column to `url_mappings` table
   - Safe: Adds column with default value of 0

2. **`2025_12_06_204946_create_path_counters_table.php`**
   - Creates new `path_counters` table
   - Safe: Creates new table, doesn't modify existing data

## Post-Migration Verification

1. **Verify migrations completed**
   ```bash
   php artisan migrate:status
   ```
   All migrations should show as "Ran"

2. **Check database structure**
   ```bash
   php artisan tinker
   ```
   Then in tinker:
   ```php
   Schema::hasColumn('url_mappings', 'hit_count'); // Should return true
   Schema::hasTable('path_counters'); // Should return true
   ```

3. **Verify Redis Configuration**
   ```bash
   php artisan tinker
   ```
   Then in tinker:
   ```php
   Redis::ping(); // Should return "PONG"
   ```
   Or test Redis connection:
   ```bash
   redis-cli ping
   ```

4. **Test the sync command manually**
   ```bash
   php artisan analytics:sync
   ```
   Should show: "Synced X paths" or "Synced 0 paths" (if no hits yet)

5. **Set up Laravel Scheduler Cron Job** (CRITICAL!)
   
   Laravel's scheduler needs a cron entry to run. Add this to your crontab:
   ```bash
   crontab -e
   ```
   
   Add this line (adjust path to your application):
   ```bash
   * * * * * cd /var/www/personal/dev-spamannihilator.com && php artisan schedule:run >> /dev/null 2>&1
   ```
   
   This runs every minute - Laravel's scheduler will then execute the `analytics:sync` command every 15 minutes automatically.

6. **Verify scheduler is working**
   ```bash
   php artisan schedule:list
   ```
   Should show `analytics:sync` scheduled to run every 15 minutes

7. **Test the application**
   - Visit admin pages to verify hit statistics display
   - Click a link to generate a hit
   - Wait up to 15 minutes and verify the hit count updates
   - Or run `php artisan analytics:sync` manually to sync immediately

## Rollback (if needed)

If something goes wrong:
```bash
# Rollback last migration
php artisan migrate:rollback --step=1

# Or rollback all migrations from a specific batch
php artisan migrate:rollback
```

## Troubleshooting

### Migration fails with "Column already exists"
- The column may have been added manually
- Check: `SHOW COLUMNS FROM url_mappings LIKE 'hit_count';`
- If it exists, you can skip this migration or modify it

### Migration fails with "Table already exists"
- The table may have been created manually
- Check: `SHOW TABLES LIKE 'path_counters';`
- If it exists, you can skip this migration or modify it

### Connection errors
- Verify database credentials in `.env`
- Check database server is running
- Verify network connectivity

## Post-Migration Checklist

After running migrations, ensure:

- [ ] Migrations completed successfully (`php artisan migrate:status`)
- [ ] Redis is configured and accessible (check `.env` has `REDIS_HOST`, `REDIS_PORT`)
- [ ] Laravel scheduler cron job is set up (runs `php artisan schedule:run` every minute)
- [ ] Sync command works (`php artisan analytics:sync` runs without errors)
- [ ] Hit statistics display on admin pages
- [ ] Test: Click a link, verify hit count updates (may take up to 15 min or run sync manually)

## Safety Notes

- ✅ Both migrations are **non-destructive** (additive only)
- ✅ No data loss risk (adds columns/tables, doesn't modify existing data)
- ✅ Can be run during normal operations (no downtime required)
- ⚠️ Always backup first!
- ⚠️ Test in staging environment first if possible
- ⚠️ **Critical**: Laravel scheduler requires a cron job to work - without it, analytics won't sync!
