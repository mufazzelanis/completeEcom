<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * A random, anonymous id per browser (cookie "mv_vid", 2 years) — no personal data in it. The
 * Conversions API sends it (hashed) as external_id, so a guest's events carry at least one
 * customer-matching key besides IP + browser (Meta Diagnostics: "Missing user data parameters").
 * Available on the very first request too, via the request attribute, before the browser has
 * the cookie.
 */
class VisitorId
{
    public const COOKIE = 'mv_vid';

    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) $request->cookie(self::COOKIE);
        $isNew = !preg_match('/^[a-z0-9]{20,40}$/', $id);
        if ($isNew) {
            $id = Str::lower(Str::random(26));
        }
        $request->attributes->set(self::COOKIE, $id);

        $response = $next($request);

        if ($isNew && $request->isMethod('GET') && method_exists($response, 'withCookie')) {
            $response->withCookie(cookie(self::COOKIE, $id, 60 * 24 * 730, '/', null, null, true, false, 'lax'));
        }

        return $response;
    }

    public static function of(?Request $request): ?string
    {
        $id = $request?->attributes->get(self::COOKIE) ?? $request?->cookie(self::COOKIE);

        return is_string($id) && $id !== '' ? $id : null;
    }
}
