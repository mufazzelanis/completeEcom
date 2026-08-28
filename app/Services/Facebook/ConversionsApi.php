<?php

namespace App\Services\Facebook;

use App\Jobs\SendFacebookConversionEvent;
use Illuminate\Http\Request;

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
     */
    public static function track(
        string $eventName,
        string $eventId,
        array $customData = [],
        array $rawUserFields = [],
        ?Request $request = null,
        ?string $eventSourceUrl = null,
        ?string $pixelIdOverride = null,
    ): void {
        if (!self::isEnabled()) {
            return;
        }

        $request ??= request();
        $pixelId = $pixelIdOverride ?: setting('facebook_pixel_id', '');
        $accessToken = setting('facebook_capi_access_token', '');
        $testEventCode = setting('facebook_capi_test_event_code', '');

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
            self::GRAPH_VERSION, $pixelId, $accessToken, $testEventCode ?: null, $event
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

        $userData['client_ip_address'] = $request->ip();
        $userData['client_user_agent'] = $request->userAgent();

        if ($fbp = $request->cookie('_fbp')) {
            $userData['fbp'] = $fbp;
        }
        if ($fbc = self::resolveFbc($request)) {
            $userData['fbc'] = $fbc;
        }
        if (auth()->check()) {
            // A consistent hashed customer identifier — helps Meta match this event to the
            // same person across devices/sessions even when em/ph aren't provided this time.
            $userData['external_id'] = self::hash((string) auth()->id());
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
        if ($fbc = $request->cookie('_fbc')) {
            return $fbc;
        }
        if ($fbclid = $request->query('fbclid')) {
            return 'fb.1.' . (int) round(microtime(true) * 1000) . '.' . $fbclid;
        }
        return null;
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
