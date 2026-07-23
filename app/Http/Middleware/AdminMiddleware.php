<?php

namespace App\Http\Middleware;

use App\Support\IpMatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access the admin panel.');
        }

        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'You do not have permission to access the admin panel.');
        }

        if (setting('ip_restriction_enabled', '0') === '1' && !$this->ipAllowed($request->ip())) {
            abort(403, 'Access to the admin panel is not allowed from your current IP address.');
        }

        if (setting('two_factor_enabled', '0') === '1' && !auth()->user()->hasTwoFactorEnabled() && !$request->routeIs('admin.two-factor.*')) {
            return redirect()->route('admin.two-factor.show')
                ->with('warning', 'Two-factor authentication is required for admin accounts. Please set it up to continue.');
        }

        $response = $next($request);

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    /**
     * An empty list almost always means "not configured yet" rather than "block
     * everyone" — enforcing it anyway would let a single misconfigured toggle
     * lock every admin out with no way back in through the UI.
     */
    private function ipAllowed(?string $ip): bool
    {
        $list = (string) setting('allowed_ips', '');

        return trim($list) === '' || IpMatcher::matchesAny($ip, $list);
    }
}
