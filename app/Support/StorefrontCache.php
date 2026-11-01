<?php

namespace App\Support;

use App\Models\Cart;
use Illuminate\Support\Facades\Cache;

/**
 * Data every storefront page needs that is the same for every visitor (header categories,
 * footer page links, …). Cached under the homepage cache version, which AppServiceProvider
 * bumps whenever one of the underlying models is saved or deleted — so admin edits show up
 * on the next page view and the TTL only bounds changes no model event announces.
 */
class StorefrontCache
{
    public const VERSION_KEY = 'home_cache_version';
    private const TTL = 300; // seconds

    private static ?int $version = null;

    public static function remember(string $key, \Closure $callback)
    {
        // read once per request; bump() resets it so a later read in the same request sees the new version
        self::$version ??= (int) Cache::get(self::VERSION_KEY, 1);

        return Cache::remember('home:' . self::$version . ':' . $key, self::TTL, $callback);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
        self::$version = null;
    }

    /** Header cart badge — shown by both the top header and the mobile bottom nav, counted once. */
    public static function cartCount(): int
    {
        return once(fn () => (int) (auth()->check()
            ? Cart::where('user_id', auth()->id())->sum('quantity')
            : Cart::where('session_id', session()->getId())->sum('quantity')));
    }
}
