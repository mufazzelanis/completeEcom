<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code', 'name', 'native_name', 'flag_emoji',
        'direction', 'is_active', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    /** Per-request cache, reset on any write — mirrors App\Models\Setting. */
    protected static ?\Illuminate\Support\Collection $requestCache = null;

    protected static function booted(): void
    {
        static::created(fn () => static::$requestCache = null);
        static::updated(fn () => static::$requestCache = null);
        static::deleted(fn () => static::$requestCache = null);
    }

    public static function active(): \Illuminate\Support\Collection
    {
        if (static::$requestCache === null) {
            static::$requestCache = static::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return static::$requestCache;
    }

    public static function default(): ?self
    {
        return static::active()->firstWhere('is_default', true) ?? static::active()->first();
    }

    public static function bust(): void
    {
        static::$requestCache = null;
    }
}
