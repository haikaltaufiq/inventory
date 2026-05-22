<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheVersions
{
    private const CATALOG = 'cache_version:catalog';
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

    private static function bump(string $key): void
    {
        if (! Cache::has($key)) {
            Cache::forever($key, 1);
        }

        Cache::increment($key);
    }
}
