<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Process-level cache for Schema::hasColumn() calls.
 *
 * Schema::hasColumn() issues a SHOW COLUMNS / PRAGMA table_info query on every
 * call. This wrapper memoises the result for the lifetime of the PHP process so
 * we pay the cost exactly once per table+column combination, not once per
 * request / per loop iteration.
 */
final class SchemaCache
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        $key = "{$table}.{$column}";

        if (! array_key_exists($key, self::$cache)) {
            self::$cache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$cache[$key];
    }

    /**
     * Convenience: does product_supplier have pemodal_user_id?
     * Called in at least 5 different places — centralise here.
     */
    public static function productSupplierHasPemodal(): bool
    {
        return self::hasColumn('product_supplier', 'pemodal_user_id');
    }

    /** Clear the in-process cache (useful in tests). */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
