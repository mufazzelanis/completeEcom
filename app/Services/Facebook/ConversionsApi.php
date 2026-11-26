<?php

namespace App\Services\Facebook;

use App\Jobs\SendFacebookConversionEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Facebook/Meta Conversions API (server-side event tracking) — the counterpart to the
 * browser-side Pixel already wired throughout the app (see resources/views/layouts/app.blade.php
 * and friends). The Pixel alone misses a large and growing share of real conversions: ad
 * blockers, iOS's Intelligent Tracking Prevention, and browsers that block third-party
 * scripts all silently drop the client-side `fbq('track', ...)` call before it ever reaches
 * Meta. Sending the *same* event from the server — over plain HTTPS, nothing to block —
 * recovers those, and passing a shared `event_id` to both the pixel and this service lets
 * Meta deduplicate the pair into one conversion rather than double-counting.
 *
 * Every call site in this app follows the same shape: generate one event_id, hand it to the
 * Blade view for the client-side fbq(...) call, and pass it to self::track() here for the
 * server-side half — see ProductController@show (ViewContent), CartController@add
 * (AddToCart), CheckoutController@index (InitiateCheckout), CheckoutController@success and
 * LandingPageController@order (Purchase).
 */
class ConversionsApi
{
    private const GRAPH_VERSION = 'v21.0';

    /** Hashed user_data fields kept per browser once known (see buildUserData). */
    private const REMEMBERED = ['em', 'ph', 'fn', 'ln', 'ct', 'st', 'zp', 'country'];

    /**
     * Meta's own normalisation for city: lowercase, letters only (no spaces or punctuation) —
     * "Cox's Bazar" → "coxsbazar". Bangla script is kept as is.
     */
    public static function normalizeCity(string $city): string
    {
        return preg_replace('/[^\p{L}]+/u', '', mb_strtolower(trim($city)));
    }

    /**
     * For a guest Meta knows nothing else about: the city (and country) of their IP, hashed —
     * from the IP lookup the site already caches for Activity Log / Live Visitors, looked up now
     * when it isn't cached yet (at most a few per minute: the free lookup allows 15 a minute and
     * the admin pages share it). Runs inside the queued job, so a visitor never waits on it.
     */
    /** Hashed ct / st / zp / country from Cloudflare's location headers, normalised Meta's way. */
    private static function cloudflareLocation(Request $request): array
    {
        $out = [];
        $city = self::normalizeCity((string) $request->header('CF-IPCity'));
        if ($city !== '') {
            $out['ct'] = self::hash($city);
        }
        $region = self::normalizeCity((string) $request->header('CF-Region'));
        if ($region !== '') {
            $out['st'] = self::hash($region);
        }
        $zip = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->header('CF-Postal-Code')));
        if ($zip !== '') {
            $out['zp'] = self::hash($zip);
        }
        $cc = strtolower((string) $request->header('CF-IPCountry'));
        if (preg_match('/^[a-z]{2}$/', $cc) && !in_array($cc, ['xx', 't1'], true)) { // XX unknown, T1 Tor
            $out['country'] = self::hash($cc);
        }

        return $out;
    }

    /**
     * True while a guest's event still has no city and their IP isn't looked up yet — the job
     * then waits a minute for the every-minute IP resolver (visitors:resolve-ips) instead of
     * going out without one (Meta Diagnostics: "Missing user data parameters").
     */
    public static function waitingForIpLocation(array $userData): bool
    {
        $ip = $userData['client_ip_address'] ?? null;
        if (!$ip || isset($userData['ct']) || isset($userData['em']) || isset($userData['ph'])) {
            return false;
        }
        if (\App\Support\VisitorIntel::ip($ip) !== null || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        \App\Support\VisitorIntel::queue([$ip]);

        return true;
    }

    public static function addIpLocation(array $userData): array
    {
        $ip = $userData['client_ip_address'] ?? null;
        if (!$ip || (isset($userData['ct']) && isset($userData['country']))) {
            return $userData;
        }
        $info = \App\Support\VisitorIntel::ip($ip);
        if ($info === null && Cache::add('capi_iplookup:' . now()->format('YmdHi') . ':' . intdiv((int) now()->format('s'), 15), 1, 120)) {
            \App\Support\VisitorIntel::lookup([$ip]); // one per 15 s at most
            $info = \App\Support\VisitorIntel::ip($ip);
        }
        if (!empty($info['ok'])) {
            if (!isset($userData['ct']) && ($city = self::normalizeCity((string) ($info['city'] ?? ''))) !== '') {
                $userData['ct'] = self::hash($city);
            }
            if (!isset($userData['country']) && !empty($info['cc'])) {
                $userData['country'] = self::hash(strtolower($info['cc']));
            }
        }

        return $userData;
    }

    /**
     * Whether the merchant has actually turned this on and given it what it needs to run —
     * every call below is a safe no-op when this is false, so call sites never need their own
     * "is CAPI configured" branch.
     */
    public static function isEnabled(): bool
    {
        return setting('facebook_capi_enabled', '0') == '1'
            && (string) setting('facebook_capi_access_token', '') !== ''
            && (string) setting('facebook_pixel_id', '') !== '';
    }

    /**
     * @param string $eventName One of Meta's standard event names: ViewContent, AddToCart,
     *                          InitiateCheckout, Purchase, etc.
     * @param string $eventId Shared with the matching client-side fbq(..., {eventID: $eventId})
     *                        call so Meta dedupes the pair instead of double-counting.
     * @param array $customData Event-specific fields: value, currency, content_ids,
     *                          content_type, contents, num_items — whatever that event
     *                          normally carries on the pixel side.
     * @param array $rawUserFields Plain (unhashed) name/email/phone/address — same shape
     *                          pixel_advanced_matching_data() already builds elsewhere in this
     *                          app; pass its ['name','email','phone','city','state','zip','country']
     *                          inputs straight through. Hashed here, right before queuing, so
     *                          raw PII never touches the jobs table.
     * @param string|null $pixelIdOverride A landing page's own fb_pixel_id, when the event
     *                          belongs to a specific campaign page rather than the site pixel.
     * @param string|null $accessTokenOverride A landing page's own fb_capi_access_token. A CAPI
     *                          access token is scoped to one specific pixel on Meta's side, so a
     *                          page using $pixelIdOverride needs its own token too — the
     *                          site-wide one is scoped to the site's own pixel and would fail
     *                          Meta's auth check (logged as a 'failed' row, same as any other
     *                          rejected event) if used for a different pixel.
     * @param int|null $landingPageId Stamped on the resulting FacebookConversionLog row only —
     *                          lets Admin → Landing Pages → Analytics answer "is this specific
     *                          page's pixel/CAPI actually working?" instead of only a site-wide total.
     * @param string|null $testEventCodeOverride A landing page's own fb_capi_test_event_code —
     *                          lets a page running its own ad account verify its events in
     *                          Meta's Test Events tool independently of the site-wide pixel's own.
     */
    public static function track(
        string $eventName,
        string $eventId,
        array $customData = [],
        array $rawUserFields = [],
        ?Request $request = null,
        ?string $eventSourceUrl = null,
        ?string $pixelIdOverride = null,
        ?string $accessTokenOverride = null,
        ?int $landingPageId = null,
        ?string $testEventCodeOverride = null,
    ): void {
        $pixelId = $pixelIdOverride ?: setting('facebook_pixel_id', '');
        $accessToken = $accessTokenOverride ?: setting('facebook_capi_access_token', '');

        // A landing page with its own pixel but no token of its own: the site token belongs to the
        // site's pixel, so Meta would reject every event — skip the server half (its browser
        // pixel still fires) instead of filling the log with guaranteed failures.
        if ($pixelIdOverride && !$accessTokenOverride && $pixelIdOverride !== (string) setting('facebook_pixel_id', '')) {
            return;
        }

        // Overridden pixel+token pairs (a landing page running its own ad account) are checked
        // directly rather than through isEnabled(), which only reflects the SITE-WIDE
        // credentials — gating a page that supplies both of its own on the site's separate
        // "Enable Conversions API" toggle would be the wrong switch for the admin to have to
        // flip, and could silently stop working if that unrelated toggle is ever turned off.
        $usingOverride = $pixelIdOverride && $accessTokenOverride;
        if (!$usingOverride && !self::isEnabled()) {
            return;
        }
        if (!$pixelId || !$accessToken) {
            return;
        }

        $request ??= request();
        $testEventCode = $testEventCodeOverride ?: setting('facebook_capi_test_event_code', '');

        $userData = self::buildUserData($rawUserFields, $request);

        $event = [
            'event_name'       => $eventName,
            'event_time'       => time(),
            'event_id'         => $eventId,
            'event_source_url' => $eventSourceUrl ?: $request->fullUrl(),
            'action_source'    => 'website',
            'user_data'        => $userData,
        ];
        if ($customData) {
            $event['custom_data'] = $customData;
        }

        SendFacebookConversionEvent::dispatch(
            self::GRAPH_VERSION, $pixelId, $accessToken, $testEventCode ?: null, $event, $landingPageId
        );
    }

    /**
     * em/ph/fn/ln/ct/st/zp/country must be sha256(lowercase(trim(value))) per Meta's spec —
     * pixel_advanced_matching_data() already lowercases/trims/formats every value into exactly
     * that shape (it feeds the browser pixel's own client-side hashing too), so this only adds
     * the hash step. fbp/fbc/client_ip_address/client_user_agent are sent as-is, never hashed.
     */
    private static function buildUserData(array $rawUserFields, Request $request): array
    {
        $raw = pixel_advanced_matching_data(
            $rawUserFields['name'] ?? null,
            $rawUserFields['email'] ?? null,
            $rawUserFields['phone'] ?? null,
            $rawUserFields['city'] ?? null,
            $rawUserFields['state'] ?? null,
            $rawUserFields['zip'] ?? null,
            $rawUserFields['country'] ?? null,
        )['fb'];

        $userData = [];
        foreach ($raw as $field => $value) {
            $userData[$field] = self::hash((string) $value);
        }

        // A customer who once ordered (or logged in) from this browser: remember their hashed
        // details against the anonymous visitor id, and send them with later guest events too —
        // Meta counts only email / phone / name / city / region / postcode as "user data", so a
        // browsing guest otherwise trips "Missing user data parameters" on every event.
        $vid = \App\Http\Middleware\VisitorId::of($request);
        if ($vid) {
            $key = 'capi_who:' . $vid;
            if (isset($userData['em']) || isset($userData['ph'])) {
                Cache::put($key, array_intersect_key($userData, array_flip(self::REMEMBERED)), now()->addDays(180));
            } elseif (is_array($known = Cache::get($key))) {
                $userData += $known;
            }
        }

        // Cloudflare's visitor-location headers (Rules → Managed Transforms → "Add visitor location
        // headers"): city, region and postcode for every request, no lookup needed
        $userData += self::cloudflareLocation($request);

        $userData['client_ip_address'] = $request->ip();
        $userData['client_user_agent'] = $request->userAgent();

        // the pixel's own cookies, passed on exactly as the browser has them (they're excluded from
        // cookie encryption in bootstrap/app.php — before that they always read as null)
        $fbp = (string) $request->cookie('_fbp');
        if (preg_match('/^fb\.\d+\.\d+\.\d+$/', $fbp)) {
            $userData['fbp'] = $fbp;
        }
        if ($fbc = self::resolveFbc($request)) {
            $userData['fbc'] = $fbc;
        }
        if (auth()->check()) {
            // A consistent hashed customer identifier — helps Meta match this event to the
            // same person across devices/sessions even when em/ph aren't provided this time.
            $userData['external_id'] = self::hash((string) auth()->id());
        } elseif ($vid = \App\Http\Middleware\VisitorId::of($request)) {
            // a guest: this browser's anonymous id (App\Http\Middleware\VisitorId) — before this a
            // guest's ViewContent/AddToCart/InitiateCheckout carried only IP + browser, which Meta
            // flags as "missing user data" and can't use for matching or optimisation
            $userData['external_id'] = self::hash('v:' . $vid);
        }
        if (!isset($userData['country']) && ($cc = \App\Support\VisitorIntel::ip($request->ip())['cc'] ?? null)) {
            $userData['country'] = self::hash(strtolower($cc)); // from the IP lookup the site already caches
        }

        return $userData;
    }

    /**
     * The `_fbc` cookie only exists once the pixel's own JS has run at least once on this
     * browser. The very first hit from a Facebook ad click — often exactly the pageview this
     * event fires on — won't have it yet, only the `fbclid` URL parameter Meta appended to the
     * ad's link. Reconstructing `fbc` from that (Meta's documented fallback format) rather than
     * dropping the field entirely is the difference between attributing that click or not.
     */
    private static function resolveFbc(Request $request): ?string
    {
        // 1. the browser's own _fbc, untouched — what Meta wants (Diagnostics: "modified fbclid in fbc")
        $cookie = (string) $request->cookie('_fbc');
        if (preg_match('/^fb\.\d+\.\d+\.[A-Za-z0-9_\-]+$/', $cookie)) {
            return $cookie;
        }

        // 2. this visit's click, built once and reused, so every later event (AddToCart, Purchase…)
        //    sends the identical value instead of a new timestamp each time
        $session = $request->hasSession() ? $request->session() : null;
        $fbclid = (string) $request->query('fbclid');
        // an fbclid that isn't plain URL-safe text was cut or mangled on its way here — sending it
        // would be exactly the "modified fbclid" Meta flags, so it's left out instead
        if ($fbclid !== '' && preg_match('/^[A-Za-z0-9_\-]{10,}$/', $fbclid)) {
            $saved = $session?->get('meta_fbc');
            if (is_string($saved) && str_ends_with($saved, '.' . $fbclid)) {
                return $saved;
            }
            $fbc = 'fb.1.' . (int) round(microtime(true) * 1000) . '.' . $fbclid;
            $session?->put('meta_fbc', $fbc);

            return $fbc;
        }

        $saved = $session?->get('meta_fbc');

        return is_string($saved) ? $saved : null;
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
