<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Storefront addresses that ended in a 404, counted (Settings → Redirects → 404 log). Records
 * only real page visits: no assets, admin, bots' WordPress probes (those already get 410 from
 * LegacyUrlRedirects) or AJAX — so what's listed is what a person (or Google) actually tried.
 */
class NotFoundLog extends Model
{
    protected $fillable = ['path', 'hits', 'last_referer', 'last_hit_at'];

    protected $casts = ['last_hit_at' => 'datetime'];

    private const SKIP = '#^/(admin|storage|build|api|livewire|r/|wp-|xmlrpc|\.well-known)|\.(js|css|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|txt|xml|json|php|env|bak|sql|zip)$#i';

    public static function record(Request $request): void
    {
        if (!$request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return;
        }
        $path = Redirect::normalizeSource('/' . ltrim($request->path(), '/'));
        if (!$path || $path === '/' || mb_strlen($path) > 500 || preg_match(self::SKIP, $path)) {
            return;
        }

        try {
            $referer = mb_substr((string) $request->headers->get('referer'), 0, 500) ?: null;
            $updated = static::where('path', $path)->increment('hits', 1, array_filter(['last_hit_at' => now(), 'last_referer' => $referer]));
            if (!$updated) {
                static::create(['path' => $path, 'hits' => 1, 'last_referer' => $referer, 'last_hit_at' => now()]);
            }
        } catch (\Throwable) {
            // never let logging a 404 turn it into a 500 (e.g. two visits racing on the insert)
        }
    }
}
