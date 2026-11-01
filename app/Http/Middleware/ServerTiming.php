<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-Timing header (shown in the browser's DevTools → Network → Timing): how long PHP took
 * to boot, the whole request, and how much of it was the database. Lets slow pages be diagnosed
 * on the live host, where nothing can be profiled directly. Durations only — no data.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $boot = (microtime(true) - $start) * 1000;
        $queries = 0;
        $dbMs = 0.0;
        DB::listen(function ($query) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $query->time;
        });

        $response = $next($request);

        $response->headers->set('Server-Timing', sprintf(
            'boot;dur=%.0f, db;dur=%.0f;desc="%d queries", app;dur=%.0f',
            $boot, $dbMs, $queries, (microtime(true) - $start) * 1000
        ));

        return $response;
    }
}
