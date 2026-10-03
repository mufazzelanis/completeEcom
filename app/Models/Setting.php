<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    private const CACHE_KEY = 'app_settings_all';

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Backed by the app's cache store (persists across requests — see allFresh()),
     * busted on every write via the model events below and the explicit bust() call
     * already made after every admin Settings save. setting() is called dozens of
     * times on every single page (header, footer, every section on the homepage,
     * ...), so this is the difference between one query-or-cache-hit per request
     * total, versus one every single time any page used to load.
     */
    protected static ?array $requestCache = null;

    protected static function booted(): void
    {
        static::created(fn () => static::bust());
        static::updated(fn () => static::bust());
        static::deleted(fn () => static::bust());
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
        Cache::forget(self::CACHE_KEY);
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
            static::$requestCache = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => static::query()->pluck('value', 'key')->all()
            );
        }

        return static::$requestCache;
    }
}
