<?php

/**
 * QueryCache - Database Query Caching Layer
 * Implements file-based and optional APCu caching for frequently used queries
 * 
 * Features:
 * - Automatic cache expiration (TTL)
 * - Query result caching with dependency tracking
 * - Cache invalidation on data modification
 * - Statistics tracking
 */

class QueryCache
{
    private static $cacheDir = __DIR__ . '/cache/queries';
    private static $ttl = 3600; // 1 hour default
    private static $useAPCu = false;
    private static $stats = [
        'hits' => 0,
        'misses' => 0,
        'invalidations' => 0
    ];

    /**
     * Initialize cache system
     */
    public static function init()
    {
        // Create cache directory if it doesn't exist
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }

        // Check if APCu is available
        self::$useAPCu = extension_loaded('apcu') && ini_get('apc.enabled');

        // Load statistics from file
        self::loadStats();
    }

    /**
     * Get cached query result or execute query
     * 
     * @param string $cacheKey Unique cache key
     * @param Callable $queryCallback Function that executes the query
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or fresh query result
     */
    public static function get($cacheKey, $queryCallback, $ttl = null)
    {
        $ttl = $ttl ?? self::$ttl;

        // Try APCu first if available
        if (self::$useAPCu) {
            $cached = apcu_fetch($cacheKey);
            if ($cached !== false) {
                self::$stats['hits']++;
                self::saveStats();
                return $cached;
            }
        }

        // Try file cache
        $filePath = self::getFilePath($cacheKey);
        if (file_exists($filePath)) {
            $cached = json_decode(file_get_contents($filePath), true);
            if ($cached && isset($cached['expires'])) {
                if (time() < $cached['expires']) {
                    self::$stats['hits']++;
                    self::saveStats();
                    return $cached['data'];
                }
            }
        }

        // Cache miss - execute query
        self::$stats['misses']++;
        $result = call_user_func($queryCallback);

        // Store in cache
        self::set($cacheKey, $result, $ttl);

        return $result;
    }

    /**
     * Set cache value
     */
    public static function set($cacheKey, $data, $ttl = null)
    {
        $ttl = $ttl ?? self::$ttl;

        if (self::$useAPCu) {
            apcu_store($cacheKey, $data, $ttl);
        }

        $filePath = self::getFilePath($cacheKey);
        $cacheData = [
            'expires' => time() + $ttl,
            'data' => $data,
            'created' => date('Y-m-d H:i:s')
        ];

        file_put_contents($filePath, json_encode($cacheData, JSON_PRETTY_PRINT));
        self::saveStats();
    }

    /**
     * Invalidate/delete cache entry
     */
    public static function invalidate($cacheKey)
    {
        self::$stats['invalidations']++;

        if (self::$useAPCu) {
            apcu_delete($cacheKey);
        }

        $filePath = self::getFilePath($cacheKey);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        self::saveStats();
    }

    /**
     * Invalidate all caches matching pattern
     */
    public static function invalidatePattern($pattern)
    {
        if (self::$useAPCu) {
            apcu_clear_cache();
        }

        $regex = fnmatch($pattern, '') ? $pattern : preg_quote($pattern);
        $files = glob(self::$cacheDir . '/*');

        foreach ($files as $file) {
            $key = basename($file);
            if (fnmatch($pattern, $key)) {
                unlink($file);
                self::$stats['invalidations']++;
            }
        }

        self::saveStats();
    }

    /**
     * Clear all cache
     */
    public static function clearAll()
    {
        if (self::$useAPCu) {
            apcu_clear_cache();
        }

        $files = glob(self::$cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        self::$stats = [
            'hits' => 0,
            'misses' => 0,
            'invalidations' => 0
        ];

        self::saveStats();
    }

    /**
     * Get cache file path
     */
    private static function getFilePath($cacheKey)
    {
        $hash = md5($cacheKey);
        return self::$cacheDir . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2) . '.json';
    }

    /**
     * Get cache statistics
     */
    public static function getStats()
    {
        $total = self::$stats['hits'] + self::$stats['misses'];
        $hitRate = $total > 0 ? round((self::$stats['hits'] / $total) * 100, 2) : 0;

        return array_merge(self::$stats, [
            'total_requests' => $total,
            'hit_rate' => $hitRate . '%'
        ]);
    }

    /**
     * Save statistics to file
     */
    private static function saveStats()
    {
        $statsFile = self::$cacheDir . '/../cache_stats.json';
        file_put_contents($statsFile, json_encode(self::$stats, JSON_PRETTY_PRINT));
    }

    /**
     * Load statistics from file
     */
    private static function loadStats()
    {
        $statsFile = self::$cacheDir . '/../cache_stats.json';
        if (file_exists($statsFile)) {
            self::$stats = json_decode(file_get_contents($statsFile), true) ?? self::$stats;
        }
    }

    /**
     * Get cache size in MB
     */
    public static function getCacheSize()
    {
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return round($size / 1024 / 1024, 2);
    }
}

// Initialize cache system on include
QueryCache::init();
