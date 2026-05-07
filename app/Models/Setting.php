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
    // ─────────────────────────────────────────────
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting_{$key}");
    }

    // ─────────────────────────────────────────────
    // SET MANY: Simpan banyak setting sekaligus
    // ─────────────────────────────────────────────
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    // ─────────────────────────────────────────────
    // GET MIDTRANS CONFIG: Ambil semua config Midtrans (Decrypted)
    // ─────────────────────────────────────────────
    public static function midtransConfig(): array
    {
        $serverKeyEncrypted = static::get('midtrans_server_key');
        $clientKeyEncrypted = static::get('midtrans_client_key');
        $env = static::get('midtrans_env', 'sandbox');

        return [
            'server_key'    => $serverKeyEncrypted ? decrypt($serverKeyEncrypted) : null,
            'client_key'    => $clientKeyEncrypted ? decrypt($clientKeyEncrypted) : null,
            'env'           => $env,
            'is_production' => $env === 'production',
        ];
    }
}
