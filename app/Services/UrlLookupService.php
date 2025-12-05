<?php

namespace App\Services;

use App\Models\UrlMapping;
use Illuminate\Support\Facades\Cache;

class UrlLookupService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'url_mapping:';
    private const CACHE_ALL_KEY = 'url_mappings:all';

    public function getUrl(string $slug): ?string
    {
        $cacheKey = self::CACHE_PREFIX . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            $mapping = UrlMapping::where('slug', $slug)->first();
            return $mapping?->url;
        });
    }

    public function slugExists(string $slug): bool
    {
        $cacheKey = self::CACHE_PREFIX . $slug;

        return Cache::remember($cacheKey . ':exists', self::CACHE_TTL, function () use ($slug) {
            return UrlMapping::where('slug', $slug)->exists();
        });
    }

    /**
     * Clear cache for a specific slug
     */
    public function clearCache(string $slug): void
    {
        Cache::forget(self::CACHE_PREFIX . $slug);
        Cache::forget(self::CACHE_PREFIX . $slug . ':exists');
        Cache::forget(self::CACHE_ALL_KEY);
    }

    /**
     * Clear all URL mapping cache
     */
    public function clearAllCache(): void
    {
        Cache::forget(self::CACHE_ALL_KEY);
        // Note: Individual cache keys will expire naturally, but we could
        // implement a more aggressive clear if needed
    }
}
