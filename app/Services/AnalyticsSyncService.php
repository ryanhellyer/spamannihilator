<?php

namespace App\Services;

use App\Models\PathCounter;
use App\Models\UrlMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AnalyticsSyncService
{
    private const REDIS_PREFIX = 'analytics:hit:';

    /**
     * Sync Redis counters to MariaDB
     *
     * Reads all analytics:hit:* keys from Redis, calculates increments,
     * and persists them to the database. Resets Redis counters after successful sync.
     *
     * @return array Summary of sync operation: ['synced' => int, 'errors' => array]
     */
    public function sync(): array
    {
        $synced = 0;
        $errors = [];
        $redisKeys = [];

        try {
            // Get all keys matching our analytics prefix
            // Note: Using keys() for simplicity. For production with very large datasets (1000+ keys),
            // consider implementing scan() for non-blocking iteration:
            //   $cursor = 0;
            //   do {
            //     $result = $redis->scan($cursor, $pattern, 100);
            //     $redisKeys = array_merge($redisKeys, $result);
            //   } while ($cursor !== 0);
            // Current usage (15-min sync intervals) typically results in <100 keys, so keys() is acceptable.
            $redisKeys = Redis::keys(self::REDIS_PREFIX . '*');
            
            if (!is_array($redisKeys)) {
                $redisKeys = [];
            }

            if (empty($redisKeys)) {
                return ['synced' => 0, 'errors' => []];
            }

            // Collect Redis values before processing (to avoid reading during transaction)
            // Redis facade's keys() returns keys WITH Laravel prefix (e.g., 'laravel-database-analytics:hit:checking/test')
            // But Redis facade's get() expects keys WITHOUT Laravel prefix (it adds it automatically)
            $redisData = [];
            $laravelPrefix = config('database.redis.options.prefix', '');
            
            foreach ($redisKeys as $fullKey) {
                try {
                    // Remove Laravel prefix and our prefix to get the path
                    // Full key: 'laravel-database-analytics:hit:checking/test'
                    // After removing prefix: 'checking/test'
                    $path = str_replace($laravelPrefix . self::REDIS_PREFIX, '', $fullKey);
                    
                    // Use Redis facade to get value - it expects key without Laravel prefix
                    // So we use our prefix + path
                    $facadeKey = self::REDIS_PREFIX . $path;
                    $redisValue = (int) Redis::get($facadeKey);
                    
                    if ($redisValue > 0) {
                        $redisData[$fullKey] = [
                            'path' => $path,
                            'value' => $redisValue,
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to read Redis key '{$fullKey}': " . $e->getMessage();
                }
            }

            if (empty($redisData)) {
                return ['synced' => 0, 'errors' => $errors];
            }

            // Process each path in individual transactions to allow partial success
            // Separate into two groups:
            // 1. /check/{slug} paths → extract slug and update url_mappings
            // 2. Everything else → update path_counters (including 404s)
            $successfulKeys = [];
            $processedKeys = []; // Track all processed keys for cleanup
            foreach ($redisData as $key => $data) {
                $processedKeys[] = $key; // Track all keys we process
                try {
                    DB::transaction(function () use ($data, &$synced, $key, &$successfulKeys) {
                        $path = $data['path'];
                        $redisValue = $data['value'];

                        // Check if this is a /check/{slug} path
                        $slug = $this->extractSlugFromCheckPath($path);

                        if ($slug !== null) {
                            // This is a /check/{slug} path - update url_mappings
                            $urlMapping = UrlMapping::where('slug', $slug)->first();
                            if (!$urlMapping) {
                                // Slug doesn't exist - skip it
                                return;
                            }

                            // Get current DB counter value
                            $currentDbValue = $urlMapping->hit_count ?? 0;

                            // Calculate new total (add Redis increment to DB value)
                            $newTotal = $currentDbValue + $redisValue;

                            // Update the url_mapping
                            $urlMapping->hit_count = $newTotal;
                            $urlMapping->save();

                            $synced++;
                            $successfulKeys[] = $key;
                        } else {
                            // This is not a /check/{slug} path - update path_counters
                            // Normalize path for storage (404s become '404')
                            $normalizedPath = $this->normalizePathForStorage($path);

                            // Get or create path counter
                            $pathCounter = PathCounter::firstOrNew(['path' => $normalizedPath]);
                            $currentDbValue = $pathCounter->hit_count ?? 0;
                            $newTotal = $currentDbValue + $redisValue;
                            $pathCounter->hit_count = $newTotal;
                            $pathCounter->save();

                            $synced++;
                            $successfulKeys[] = $key;
                        }
                    });
                } catch (\Exception $e) {
                    $errors[] = "Failed to sync path '{$data['path']}': " . $e->getMessage();
                    \Log::error('AnalyticsSyncService: Error syncing path', [
                        'path' => $data['path'],
                        'error' => $e->getMessage(),
                    ]);
                    // Continue processing other keys
                }
            }

            // After successful DB updates, reset Redis counters
            // Delete all processed keys (both synced and skipped) to clean up Redis
            // Need to use facade key format (without Laravel prefix) for Redis::del()
            $laravelPrefix = config('database.redis.options.prefix', '');
            foreach ($processedKeys as $fullKey) {
                try {
                    // Extract path from full key (fullKey is from redisData, which uses full key as array key)
                    $path = str_replace($laravelPrefix . self::REDIS_PREFIX, '', $fullKey);
                    // Use facade key format (Redis facade handles Laravel prefix automatically)
                    $facadeKey = self::REDIS_PREFIX . $path;
                    Redis::del($facadeKey);
                } catch (\Exception $e) {
                    $errors[] = "Failed to reset Redis key '{$fullKey}': " . $e->getMessage();
                    \Log::error('AnalyticsSyncService: Error resetting Redis key', [
                        'key' => $fullKey,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            $errors[] = "Sync failed: " . $e->getMessage();
            \Log::error('AnalyticsSyncService: Sync operation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Extract slug from /check/{slug} paths
     *
     * Extracts the slug from paths like '/check/example-slug' or 'check/example-slug'.
     * Returns null for other paths.
     *
     * @param string $path The tracked path (e.g., '/check/example-slug')
     * @return string|null The slug if found, null otherwise
     */
    private function extractSlugFromCheckPath(string $path): ?string
    {
        // Handle full paths like '/check/example-slug'
        if (preg_match('#^/check/(.+)$#', $path, $matches)) {
            return $matches[1];
        }
        
        // Handle paths without leading slash: 'check/example-slug'
        if (preg_match('#^check/(.+)$#', $path, $matches)) {
            return $matches[1];
        }
        
        // Not a /check/{slug} path
        return null;
    }

    /**
     * Normalize path for storage in path_counters table
     *
     * Handles special cases like 404s and normalizes paths.
     *
     * @param string $path The tracked path
     * @return string Normalized path for storage
     */
    private function normalizePathForStorage(string $path): string
    {
        // 404s are already tracked as '404' by middleware, so return as-is
        if ($path === '404') {
            return '404';
        }
        
        // Normalize path: ensure leading slash for consistency
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        
        return $path;
    }
}
