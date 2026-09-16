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

        // 'unsafe-inline'/'unsafe-eval' on script-src are required by this stack (Tailwind's
        // CDN build injects styles at runtime, Alpine.js evaluates x-data/x-on expressions via
        // `new Function()`, and most pages carry inline <script> blocks rather than external
        // files) — a stricter nonce-based policy would need those rewritten across every view.
        // Even so, restricting *which domains* can be loaded at all is meaningful defense in
        // depth against injected third-party script/frame/object sources.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // googleads.g.doubleclick.net added for Google Ads conversion tracking
            // (resources/views/admin/settings/google_ads.blade.php) — once a Google
            // Ads Conversion ID is configured, gtag.js itself loads a remarketing
            // script from this domain; without it here that load is silently blocked.
            // pagead2.googlesyndication.com (AdSense) and the Adsterra domains were
            // missing entirely — every adsbygoogle.js and Adsterra invoke.js load was
            // being silently blocked by this exact policy on every single page, which
            // is the actual reason zero ad impressions were ever recorded (found via a
            // real-browser console-error audit, not something curl/view-source shows).
            // Adsterra serves invoke.js from a different numbered subdomain per zone
            // (e.g. pl31360937.profitableratecpmnetwork.com), hence the wildcard.
            // *.adtrafficquality.google added alongside connect-src's entry for the same
            // domain (see below) — AdSense's SODAR anti-fraud check loads an actual script
            // (ep2.adtrafficquality.google/sodar/sodar2.js) in addition to the beacon ping.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google.com https://www.gstatic.com https://connect.facebook.net https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://*.googlesyndication.com https://*.profitableratecpmnetwork.com https://*.highrevenueformat.com https://*.adtrafficquality.google",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            // cdn.jsdelivr.net added for the Summernote editor's bundled icon font
            // (resources/views/partials/rich-editor.blade.php) — same CDN already
            // trusted on script-src/style-src above.
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https:",
            // Ad-tech networks (Adsterra confirmed, likely AdSense too) serve the actual
            // ad creative/tracking pixels from throwaway, constantly-rotating domain names
            // (e.g. kettledroopingcontinuation.com, protrafficinspector.com — neither is
            // Adsterra's own domain, and neither existed in any prior check) specifically to
            // survive ad-blocklists. Naming each one here is a losing game — a new domain
            // shows up the next time an ad rotates. Opened to any HTTPS origin instead, the
            // same way img-src below already is, since an ad's creative/beacon can
            // legitimately come from anywhere. script-src stays a fixed allowlist (the one
            // directive that actually matters for XSS defense) — this only affects where an
            // already-trusted script (loaded from a domain named above) is allowed to fetch
            // from or open an iframe to.
            "connect-src 'self' https:",
            // www.youtube.com added for landing page video embeds (how-it-works video,
            // testimonial videos — see embed_video_url() in app/Helpers.php, which always
            // rewrites whatever URL the admin pastes to a youtube.com/embed/... iframe src).
            // Opened to any HTTPS origin for the same rotating-ad-domain reason as
            // connect-src above — the 'iframe' format ads render their actual creative in an
            // iframe from a different domain every time, not a fixed one.
            "frame-src 'self' https:",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
        ]));

        if (setting('force_https', '0') === '1' && !app()->environment('local', 'testing')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
