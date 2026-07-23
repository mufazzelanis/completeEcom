<?php

namespace App\Http\Middleware;

use App\Support\IpMatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (setting('maintenance_mode', '0') !== '1') {
            return $next($request);
        }

        // Admin routes and auth actions always stay reachable — otherwise nobody
        // could ever log in to turn maintenance mode back off.
        if ($request->is('admin*') || $request->routeIs(['login', 'logout', 'password.*', 'two-factor.*'])) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->canAccessAdmin()) {
            return $next($request);
        }

        if (IpMatcher::matchesAny($request->ip(), (string) setting('maintenance_allowed_ips', ''))) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => setting('maintenance_message', "We are currently performing scheduled maintenance. We'll be back shortly!"),
            'backTime' => setting('maintenance_back_time', ''),
            'bannerUrl' => setting_file_url('maintenance_banner'),
        ], 503);
    }
}
