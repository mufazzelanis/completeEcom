<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-party Meta cookies set by the server — what Meta's own Conversions API "Parameter
 * Builder" does. The pixel script loads a few seconds after the page (for speed), so on a
 * visitor's first page there was no _fbp yet and the server's ViewContent went without a Browser
 * ID (Live Tracking → Meta events: "Browser ID –"). Now the first response already carries:
 *   _fbp  fb.1.<ms>.<random>          — the browser id, kept 90 days
 *   _fbc  fb.1.<ms>.<fbclid>          — the ad click, when the visit came from a Facebook ad
 * in exactly Meta's format and on the same domain the pixel uses, so when fbevents.js arrives it
 * adopts these same values: browser and server events share one id. Only on page views (GET,
 * HTML), only where a pixel is set up, never for bots. Both names are excluded from cookie
 * encryption (bootstrap/app.php), so the values are readable by the pixel as they are.
 */
class MetaCookies
{
    private const DAYS = 90;

    public function handle(Request $request, Closure $next): Response
    {
        $set = $this->decide($request);
        foreach ($set as $name => $value) {
            $request->cookies->set($name, $value); // this request's own server events see them too
        }

        $response = $next($request);

        if ($set && method_exists($response, 'withCookie')) {
            $domain = self::cookieDomain($request->getHost());
            foreach ($set as $name => $value) {
                $response->withCookie(cookie($name, $value, self::DAYS * 1440, '/', $domain, $request->isSecure(), false, false, 'lax'));
            }
        }

        return $response;
    }

    /** name → value of the cookies to (re)set on this request */
    private function decide(Request $request): array
    {
        if (!$request->isMethod('GET') || $request->expectsJson() || $request->is('admin*', 'api/*', 'storage/*', 'build/*')) {
            return [];
        }
        if (!self::pixelInUse($request) || ActivityLog::automationSignal($request) !== null) {
            return [];
        }

        $set = [];
        if (!preg_match('/^fb\.\d\.\d{10,13}\.\d+$/', (string) $request->cookie('_fbp'))) {
            $set['_fbp'] = 'fb.1.' . self::nowMs() . '.' . random_int(1000000000, 2147483647);
        }
        $fbclid = (string) $request->query('fbclid');
        if (preg_match('/^[A-Za-z0-9_\-]{10,500}$/', $fbclid) && !str_ends_with((string) $request->cookie('_fbc'), '.' . $fbclid)) {
            $set['_fbc'] = 'fb.1.' . self::nowMs() . '.' . $fbclid; // the fbclid exactly as Meta sent it
        }

        return $set;
    }

    private static function pixelInUse(Request $request): bool
    {
        return trim((string) setting('facebook_pixel_id', '')) !== '' || $request->routeIs('landing.show');
    }

    private static function nowMs(): string
    {
        return (string) (int) floor(microtime(true) * 1000);
    }

    /** ".mitavin.com" for mitavin.com / www.mitavin.com (as the pixel sets it); none for localhost / an IP */
    public static function cookieDomain(string $host): ?string
    {
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) || !str_contains($host, '.')) {
            return null;
        }

        return '.' . preg_replace('/^www\./i', '', $host);
    }
}
