<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheVersions
{
    private const CATALOG   = 'cache_version:catalog';
    private const PC_BUILDS = 'cache_version:pc_builds';

    public static function catalog(): int
    {
        return (int) Cache::rememberForever(self::CATALOG, fn () => 1);
    }

    public static function pcBuilds(): int
    {
        return (int) Cache::rememberForever(self::PC_BUILDS, fn () => 1);
    }

    public static function bumpCatalog(): void
    {
        self::bump(self::CATALOG);
    }

    public static function bumpPcBuilds(): void
    {
        self::bump(self::PC_BUILDS);
    }

    /**
     * Atomically increment the version counter.
     *
     * Cache::increment() returns false when the key does not exist (for drivers
     * that don't support atomic increment on a missing key, e.g. file cache).
     * In that case we initialise with 2 so the version number is always > 0
     * and distinct from the initial rememberForever value of 1.
     */
    private static function bump(string $key): void
    {
        $result = Cache::increment($key);

        if ($result === false) {
            Cache::forever($key, 2);
        }
    }
}
