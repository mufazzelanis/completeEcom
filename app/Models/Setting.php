<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected static ?Collection $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return static::getAllCached()->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        static::bust();
    }

    public static function setMany(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value, $group);
        }
        static::bust();
    }

    public static function bust(): void
    {
        static::$cache = null;
        Cache::forget('settings.all');
    }

    public static function fileUrl(string $key, ?string $default = null): ?string
    {
        $path = static::get($key);
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        return $default;
    }

    protected static function getAllCached(): Collection
    {
        if (static::$cache === null) {
            static::$cache = Cache::rememberForever(
                'settings.all',
                fn() => static::pluck('value', 'key')
            );
        }
        return static::$cache;
    }
}
