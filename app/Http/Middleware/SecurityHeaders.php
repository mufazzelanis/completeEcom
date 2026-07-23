<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            return $response;
        }

        // Clickjacking protection — this app is never meant to be framed by another site.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Stops browsers guessing/executing a response as a different content type than declared.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Limits how much of this site's URL leaks to external links the user clicks.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Legacy header, harmless to keep for older browsers alongside CSP frame-ancestors.
        $response->headers->set('X-XSS-Protection', '0');

        if (setting('force_https', '0') === '1' && !app()->environment('local', 'testing')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
