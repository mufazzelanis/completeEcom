<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * One admin-managed redirect (Settings → Redirects): an old address → a new one. Sources are
 * stored normalized (see normalizeSource) so the admin can paste an address exactly as Google
 * shows it — full URL or path, with or without a trailing slash, any letter case, percent-
 * encoded or not — and it still matches. The whole active set is cached as one map for the
 * CustomRedirects middleware; any change clears it.
 */
class Redirect extends Model
{
    public const CACHE_KEY = 'custom_redirects_map';

    /** Paths that must never be redirected — the admin, login and checkout keep working whatever is entered. */
    public const PROTECTED_PREFIXES = ['/admin', '/login', '/logout', '/register', '/checkout', '/cart', '/api', '/storage', '/build', '/up', '/r/', '/orders', '/account', '/two-factor', '/forgot-password', '/reset-password', '/email', '/livewire'];

    protected $fillable = ['source', 'target', 'status_code', 'is_active', 'hits', 'last_hit_at', 'note'];

    protected $casts = ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];

    protected static function booted(): void
    {
        $forget = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * "https://www.mitavin.com/Old-Page/?a=1" → "/old-page?a=1". Only this site's own addresses
     * are accepted as sources; returns null for anything that can't be one.
     */
    public static function normalizeSource(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        // "mitavin.com/old-page" pasted without https:// — this site's own host only, so a
        // path like "old-page.html" is never mistaken for a domain
        $ownHost = preg_replace('/^www\./i', '', (string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($ownHost !== '' && preg_match('#^(www\.)?' . preg_quote($ownHost, '#') . '(/|\?|$)#i', $input)) {
            $input = 'https://' . $input;
        }
        if (preg_match('#^(https?:)?//#i', $input)) {
            $host = preg_replace('/^www\./i', '', (string) parse_url($input, PHP_URL_HOST));
            $ownHost = preg_replace('/^www\./i', '', (string) parse_url(config('app.url'), PHP_URL_HOST));
            if ($host !== '' && strcasecmp($host, $ownHost) !== 0 && strcasecmp($host, request()->getHost()) !== 0) {
                return null;
            }
            $path = (string) parse_url($input, PHP_URL_PATH);
            $query = parse_url($input, PHP_URL_QUERY);
        } else {
            [$path, $query] = array_pad(explode('?', $input, 2), 2, null);
        }

        $path = '/' . ltrim(rawurldecode(explode('#', $path)[0]), '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $path = mb_strtolower(preg_replace('#/+#', '/', $path));
        $query = trim((string) $query);

        return $path . ($query !== '' ? '?' . $query : '');
    }

    /** A target as an absolute URL ("/products/x" → "https://mitavin.com/products/x"); null if it isn't one. */
    public static function normalizeTarget(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        if (str_starts_with($input, '/') && !str_starts_with($input, '//')) {
            return url($input);
        }
        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }

        return filter_var($input, FILTER_VALIDATE_URL) ? $input : null;
    }

    public static function isProtected(string $source): bool
    {
        $path = explode('?', $source, 2)[0];
        if ($path === '/') {
            return true;
        }
        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, rtrim($prefix, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /** The source form of a target on this site (for loop/chain checks), or null for another site. */
    public static function targetAsSource(?string $target): ?string
    {
        if (!$target) {
            return null;
        }
        $host = preg_replace('/^www\./i', '', (string) parse_url($target, PHP_URL_HOST));
        $ownHost = preg_replace('/^www\./i', '', (string) parse_url(config('app.url'), PHP_URL_HOST));

        return strcasecmp($host, $ownHost) === 0 || strcasecmp($host, request()->getHost()) === 0
            ? self::normalizeSource($target)
            : null;
    }

    /** source => [id, target, status_code] for every active redirect. */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::where('is_active', true)
            ->get(['id', 'source', 'target', 'status_code'])
            ->mapWithKeys(fn ($r) => [$r->source => [$r->id, $r->target, $r->status_code]])
            ->all());
    }
}
