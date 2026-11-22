<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LandingPage;
use App\Models\LandingPageCtaClick;
use App\Models\LandingPageVisit;
use App\Services\Facebook\ConversionsApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Receives the landing-page tracker's beacons (landing/partials/tracker): a "start" when the
 * page is shown, periodic "ping"s with active time + deepest scroll, a "cta" for every "Order
 * Now" tap, and a "form" the first time a visitor actually touches an order-form field. One
 * landing_page_visits row per page view, keyed by the browser-made visit_key; scroll/time
 * values only ever grow (GREATEST), so out-of-order or repeated beacons are harmless. Mirrors
 * BlogTrackController's shape exactly.
 */
class LandingPageTrackController extends Controller
{
    private const MAX_SECONDS = 3600;

    private const MAX_CLICKS = 30;

    public function __invoke(Request $request)
    {
        // a bad beacon gets a quiet 204 — never Laravel's redirect-back, which fetch() would
        // follow straight into the page and record a phantom view
        $v = validator($request->all(), [
            'r'   => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'k'   => ['required', 'string', 'size:32'],
            'v'   => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'p'   => ['required', 'integer'],
            't'   => ['nullable', 'integer', 'min:0'],
            's'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'e'   => ['nullable', 'in:start,ping,cta,form'],
            'pl'  => ['nullable', 'in:' . implode(',', array_keys(LandingPageCtaClick::PLACEMENTS))],
            'ref' => ['nullable', 'string', 'max:1000'],
            'q'   => ['nullable', 'string', 'max:1000'],
        ]);
        if ($v->fails()) {
            return response()->noContent();
        }
        $d = $v->validated();

        if (!hash_equals(LandingPageVisit::sign($d['r'], (int) $d['p']), $d['k'])
            || ActivityLog::automationSignal($request) !== null
            || auth()->user()?->canAccessAdmin()
            || !$this->isLivePage((int) $d['p'])) {
            return response()->noContent();
        }

        $ua = mb_substr((string) $request->userAgent(), 0, 500);
        $visit = LandingPageVisit::firstOrCreate(['visit_key' => $d['r']], [
            'landing_page_id' => (int) $d['p'],
            'visitor_key'     => $d['v'],
            'visit_date'      => now(setting('timezone', 'Asia/Dhaka'))->toDateString(),
            'source'          => BlogTrackController::source($d['ref'] ?? null, $d['q'] ?? null, $request->getHost()),
            'referrer_host'   => BlogTrackController::host($d['ref'] ?? null),
            'device'          => BlogTrackController::device($ua),
        ]);
        if ($visit->landing_page_id !== (int) $d['p']) {
            return response()->noContent();
        }

        if (!$visit->engaged_at) {
            LandingPageVisit::whereKey($visit->id)->toBase()->update([
                'engaged_at'  => now(),
                'visitor_key' => $d['v'],
            ]);
        }

        $elapsed = $visit->created_at ? (int) $visit->created_at->diffInSeconds(now(), true) : 0;
        $seconds = min((int) ($d['t'] ?? 0), self::MAX_SECONDS, $elapsed + 40);
        $scroll = (int) ($d['s'] ?? 0);
        if ($seconds > $visit->active_seconds || $scroll > $visit->max_scroll) {
            LandingPageVisit::whereKey($visit->id)->update([
                'active_seconds' => DB::raw('GREATEST(active_seconds, ' . $seconds . ')'),
                'max_scroll'     => DB::raw('GREATEST(max_scroll, ' . $scroll . ')'),
                'updated_at'     => now(),
            ]);
        } elseif (($d['e'] ?? null) === 'ping') {
            LandingPageVisit::whereKey($visit->id)->toBase()->update(['updated_at' => now()]);
        }

        $event = $d['e'] ?? null;

        if ($event === 'cta' && $visit->cta_clicks < self::MAX_CLICKS) {
            LandingPageCtaClick::create([
                'landing_page_id'       => $visit->landing_page_id,
                'landing_page_visit_id' => $visit->id,
                'placement'             => $d['pl'] ?? 'hero',
            ]);
            LandingPageVisit::whereKey($visit->id)->toBase()->increment('cta_clicks');
        }

        // "Started filling in the order form" (AddToCart) — stamped once, the first time it
        // happens, and fires the matching server-side CAPI event right here so an ad-blocked/
        // ITP-affected browser's missed pixel call still reaches Meta. eventID format must
        // exactly match the client's fbq() call for the same milestone (see
        // landing/partials/tracker.blade.php) so Meta dedupes the pair. "Reached the order
        // form" (InitiateCheckout) is a separate, IntersectionObserver-verified signal handled
        // by reachedForm() below — a CTA tap only means "intends to scroll there", not "it's
        // actually on screen", so it must not be conflated with that milestone.
        if ($event === 'form' && !$visit->form_start_at) {
            LandingPageVisit::whereKey($visit->id)->toBase()->update(['form_start_at' => now()]);
            $this->fireCapi($visit, 'AddToCart', 'form_start_' . $visit->visit_key);
        }

        return response()->noContent();
    }

    /**
     * Separate endpoint hit from the #order-form IntersectionObserver (the same trigger that
     * already fired the client-side InitiateCheckout pixel event before this feature existed) —
     * kept distinct from __invoke()'s validated() shape since it carries its own eventId instead
     * of a placement/event-type pair.
     */
    public function reachedForm(Request $request)
    {
        $v = validator($request->all(), [
            'r' => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'k' => ['required', 'string', 'size:32'],
            'p' => ['required', 'integer'],
        ]);
        if ($v->fails()) {
            return response()->noContent();
        }
        $d = $v->validated();

        if (!hash_equals(LandingPageVisit::sign($d['r'], (int) $d['p']), $d['k'])
            || ActivityLog::automationSignal($request) !== null
            || auth()->user()?->canAccessAdmin()) {
            return response()->noContent();
        }

        $visit = LandingPageVisit::where('visit_key', $d['r'])->where('landing_page_id', (int) $d['p'])->first();
        if (!$visit || $visit->reached_form_at) {
            return response()->noContent();
        }

        LandingPageVisit::whereKey($visit->id)->toBase()->update(['reached_form_at' => now()]);
        $this->fireCapi($visit, 'InitiateCheckout', 'reached_form_' . $visit->visit_key);

        return response()->noContent();
    }

    private function fireCapi(LandingPageVisit $visit, string $eventName, string $eventId): void
    {
        $landingPage = LandingPage::find($visit->landing_page_id);
        if (!$landingPage) {
            return;
        }

        ConversionsApi::track(
            eventName: $eventName,
            eventId: $eventId,
            customData: [
                'content_ids'  => [$landingPage->product_id ? (string) $landingPage->product_id : $landingPage->slug],
                'content_type' => 'product',
                'value'        => (float) ($landingPage->effective_price ?? 0),
                'currency'     => setting('currency_code', 'BDT'),
            ],
            pixelIdOverride: $landingPage->fb_pixel_id ?: null,
            accessTokenOverride: $landingPage->fb_capi_access_token ?: null,
            landingPageId: $landingPage->id,
            testEventCodeOverride: $landingPage->fb_capi_test_event_code ?: null,
        );
    }

    private function isLivePage(int $id): bool
    {
        return Cache::remember("lp-track:live:{$id}", 600, fn () => LandingPage::where('status', 'published')->whereKey($id)->exists());
    }
}
