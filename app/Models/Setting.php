<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * No cross-request cache — every request reads settings straight from the
     * database. This is only a per-request reuse so a single page load that calls
     * setting() dozens of times doesn't run dozens of identical queries; it is
     * reset (see boot()) at the start of every request and never persisted.
     */
    protected static ?array $requestCache = null;

    protected static function booted(): void
    {
        static::created(fn () => static::$requestCache = null);
        static::updated(fn () => static::$requestCache = null);
        static::deleted(fn () => static::$requestCache = null);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allFresh()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    public static function setMany(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value, $group);
        }
    }

    public static function bust(): void
    {
        static::$requestCache = null;
    }

    public static function fileUrl(string $key, ?string $default = null): ?string
    {
        $path = static::get($key);
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        return $default;
    }

    private static function allFresh(): array
    {
        if (static::$requestCache === null) {
            static::$requestCache = static::query()->pluck('value', 'key')->all();
        }

        return static::$requestCache;
    }
}
