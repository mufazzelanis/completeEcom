<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = ['key', 'locale', 'value', 'group'];

    /** Per-request cache keyed by locale, reset on any write — mirrors App\Models\Setting. */
    protected static ?array $requestCache = null;

    protected static function booted(): void
    {
        static::created(fn () => static::$requestCache = null);
        static::updated(fn () => static::$requestCache = null);
        static::deleted(fn () => static::$requestCache = null);
    }

    public static function get(string $key, string $locale, ?string $default = null): ?string
    {
        $value = static::forLocale($locale)[$key] ?? null;

        return ($value !== null && $value !== '') ? $value : $default;
    }

    public static function set(string $key, string $locale, string $value, string $group = 'common'): void
    {
        static::updateOrCreate(
            ['key' => $key, 'locale' => $locale],
            ['value' => $value, 'group' => $group]
        );
    }

    public static function bust(): void
    {
        static::$requestCache = null;
    }

    private static function forLocale(string $locale): array
    {
        static::$requestCache ??= [];

        if (!array_key_exists($locale, static::$requestCache)) {
            static::$requestCache[$locale] = static::query()
                ->where('locale', $locale)
                ->pluck('value', 'key')
                ->all();
        }

        return static::$requestCache[$locale];
    }
}
