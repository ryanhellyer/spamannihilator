<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class AnalyticsService
{
    private const REDIS_PREFIX = 'analytics:hit:';

    /**
     * Track a hit on a path by incrementing Redis counter
     *
     * @param string $path The path/route being tracked
     * @return void
     */
    public function trackHit(string $path): void
    {
        try {
            Redis::incr(self::REDIS_PREFIX . $path);
        } catch (\Exception $e) {
            // Fail gracefully - log error but don't break the request
            \Log::error('AnalyticsService: Failed to track hit', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current hit count for a path from Redis
     *
     * @param string $path The path/route to check
     * @return int Current hit count (0 if not found)
     */
    public function getHitCount(string $path): int
    {
        try {
            $value = Redis::get(self::REDIS_PREFIX . $path);
            return $value ? (int) $value : 0;
        } catch (\Exception $e) {
            \Log::error('AnalyticsService: Failed to get hit count', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
