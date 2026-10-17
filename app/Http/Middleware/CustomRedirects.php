<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin's redirects (Settings → Redirects) before any route runs — global, so an
 * old address that no longer matches any route (most of what Google still has indexed) is
 * caught too. One cached map lookup per request; a database write only when a redirect is
 * actually used (its hit counter).
 *
 * An exact match including the query string wins over a match on the path alone. When only
 * the path matched, the visitor's own query string (utm_source, fbclid, …) is carried over to
 * the target so campaign tracking survives the redirect.
 */
class CustomRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = Redirect::normalizeSource('/' . ltrim($request->path(), '/'));
        if (!$path || Redirect::isProtected($path)) {
            return $next($request);
        }

        try {
            $map = Redirect::map();
        } catch (\Throwable) {
            return $next($request); // table not migrated yet — never take the site down
        }
        if (!$map) {
            return $next($request);
        }

        $query = (string) $request->getQueryString();
        $exact = $query !== '' ? ($map[Redirect::normalizeSource($path . '?' . $query)] ?? null) : null;
        $hit = $exact ?? ($map[$path] ?? null);
        if (!$hit) {
            return $next($request);
        }

        [$id, $target, $code] = $hit;
        DB::table('redirects')->where('id', $id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);

        if ((int) $code === 410 || !$target) {
            abort(410);
        }
        if (!$exact && $query !== '' && !str_contains($target, '?')) {
            $target .= '?' . $query;
        }

        return redirect()->away($target, (int) $code === 302 ? 302 : 301);
    }
}
