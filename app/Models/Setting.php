<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'key',
        'value'
    ];

    // ─────────────────────────────────────────────
    // GET: Ambil satu nilai setting
    // Contoh: Setting::get('midtrans_server_key')
    // ─────────────────────────────────────────────
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $setting = static::find($key);
            return $setting?->value ?? $default;
        });
    }

    // ─────────────────────────────────────────────
    // SET: Simpan atau update satu nilai setting
    // Contoh: Setting::set('midtrans_env', 'sandbox')
    // ─────────────────────────────────────────────
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting_{$key}");
    }

    // ─────────────────────────────────────────────
    // SET MANY: Simpan banyak setting sekaligus
    // Contoh: Setting::setMany(['key' => 'val', ...])
    // ─────────────────────────────────────────────
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    // ─────────────────────────────────────────────
    // GET MIDTRANS CONFIG: Ambil semua config Midtrans
    // ─────────────────────────────────────────────
    public static function midtransConfig(): array
    {
        return [
            'server_key' => static::get('midtrans_server_key'),
            'client_key' => static::get('midtrans_client_key'),
            'env'        => static::get('midtrans_env', 'sandbox'),
            'is_production' => static::get('midtrans_env', 'sandbox') === 'production',
        ];
    }
}
