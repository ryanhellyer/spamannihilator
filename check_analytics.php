<?php
/**
 * Quick diagnostic script to check analytics tracking
 * Run: php check_analytics.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== Analytics Diagnostic ===\n\n";

// Check Redis connection
try {
    $redis = Redis::connection();
    $ping = $redis->ping();
    echo "✓ Redis connection: OK ($ping)\n";
} catch (\Exception $e) {
    echo "✗ Redis connection: FAILED - {$e->getMessage()}\n";
    exit(1);
}

// Check for analytics keys
try {
    $keys = Redis::keys('analytics:hit:*');
    echo "\nRedis keys found: " . count($keys) . "\n";
    
    if (count($keys) > 0) {
        echo "\nAnalytics keys in Redis:\n";
        foreach ($keys as $key) {
            $value = Redis::get($key);
            echo "  - $key = $value\n";
        }
    } else {
        echo "\n⚠ No analytics keys found in Redis.\n";
        echo "This means either:\n";
        echo "  1. No hits have been tracked yet (try visiting /check/{slug} to generate a hit)\n";
        echo "  2. Tracking isn't working (check middleware/controller tracking)\n";
        echo "  3. Keys were already synced and cleared\n";
        echo "\nTo test tracking:\n";
        echo "  1. Visit a link like: /check/{your-slug}\n";
        echo "  2. Run this script again to see if keys appear\n";
    }
} catch (\Exception $e) {
    echo "✗ Error checking Redis keys: {$e->getMessage()}\n";
}

// Check database
try {
    $dbHits = \App\Models\UrlMapping::sum('hit_count');
    echo "\nDatabase hit_count sum: $dbHits\n";
    
    $pathCounters = \App\Models\PathCounter::sum('hit_count');
    echo "PathCounter hit_count sum: $pathCounters\n";
} catch (\Exception $e) {
    echo "\n✗ Error checking database: {$e->getMessage()}\n";
}

echo "\n=== End Diagnostic ===\n";
