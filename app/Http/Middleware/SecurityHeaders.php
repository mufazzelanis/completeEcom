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
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google.com https://www.gstatic.com https://connect.facebook.net https://googleads.g.doubleclick.net https://pagead2.googlesyndication.com https://*.googlesyndication.com https://*.profitableratecpmnetwork.com https://*.highrevenueformat.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            // cdn.jsdelivr.net added for the Summernote editor's bundled icon font
            // (resources/views/partials/rich-editor.blade.php) — same CDN already
            // trusted on script-src/style-src above.
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https:",
            // www.google.com, googleads.g.doubleclick.net and *.doubleclick.net added
            // for the same Google Ads conversion tracking as script-src above — gtag.js
            // sends its conversion/remarketing beacons (rmkt/collect, ccm/collect) to
            // these once a Conversion ID is configured. AdSense/Adsterra domains added
            // since both fire additional XHR/beacon calls beyond the initial script load
            // (impression/viewability pings) that connect-src would otherwise still block
            // even after script-src and frame-src allow the script and iframe through.
            // *.adtrafficquality.google is AdSense's own "SODAR" traffic-quality/anti-fraud
            // ping (ep1/ep2.adtrafficquality.google) — found via a live console-error check
            // right after adding the domains above; adsbygoogle.js fires this on every load.
            "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://www.facebook.com https://www.google.com https://googleads.g.doubleclick.net https://*.doubleclick.net https://www.googleadservices.com https://pagead2.googlesyndication.com https://*.googlesyndication.com https://*.profitableratecpmnetwork.com https://*.highrevenueformat.com https://*.adtrafficquality.google",
            // www.youtube.com added for landing page video embeds (how-it-works video,
            // testimonial videos — see embed_video_url() in app/Helpers.php, which always
            // rewrites whatever URL the admin pastes to a youtube.com/embed/... iframe src).
            // googleads.g.doubleclick.net/*.doubleclick.net (AdSense) and the Adsterra
            // domains added because the 'iframe' format ads (Settings → Advertisements)
            // render their actual creative inside an iframe, not just a <script> tag —
            // script-src alone isn't enough to let that iframe's content load.
            "frame-src 'self' https://www.google.com https://www.facebook.com https://www.youtube.com https://googleads.g.doubleclick.net https://*.doubleclick.net https://*.googlesyndication.com https://*.profitableratecpmnetwork.com https://*.highrevenueformat.com",
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
