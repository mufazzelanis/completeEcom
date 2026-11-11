{{-- Google Tag Manager + Microsoft Clarity (Settings → Google Analytics & Ads), each with its
     own on/off switch. Shared by the storefront and landing-page layouts.

     Both load only on the visitor's first scroll/tap/keypress, or once the page has finished
     loading (GTM +3 s, Clarity +10 s), whichever comes first — like the Facebook Pixel — so
     neither costs PageSpeed. Nothing is lost by waiting: GTM reads every event already queued
     in window.dataLayer (view_item, add_to_cart, purchase … pushed by mvTrack) when it starts,
     and Clarity records the session from that point.

     IDs are only used when they match the expected format (they're written into a script). --}}
@php
    $gtmId = setting('google_tag_manager_enabled', '1') === '1' ? strtoupper(trim((string) setting('google_tag_manager_id', ''))) : '';
    $gtmId = preg_match('/^GTM-[A-Z0-9]{4,12}$/', $gtmId) ? $gtmId : '';
    $clarityId = setting('clarity_enabled', '0') === '1' ? strtolower(trim((string) setting('clarity_project_id', ''))) : '';
    $clarityId = preg_match('/^[a-z0-9]{6,16}$/', $clarityId) ? $clarityId : '';
@endphp
@if($gtmId || $clarityId)
<script>
(function (w, d) {
    if (w.__mvTagsQueued) return; // once per real page load (Turbo re-runs head scripts it hasn't seen)
    w.__mvTagsQueued = true;
    // run fn once: on the visitor's first scroll/tap/key, or `idle` ms after the page has loaded
    var whenReady = function (fn, idle) {
        var done = false, evs = ['scroll', 'pointerdown', 'keydown', 'touchstart'];
        var go = function () {
            if (done) return;
            done = true;
            evs.forEach(function (ev) { w.removeEventListener(ev, go); });
            fn();
        };
        evs.forEach(function (ev) { w.addEventListener(ev, go, { passive: true }); });
        if (d.readyState === 'complete') { setTimeout(go, idle); } else { w.addEventListener('load', function () { setTimeout(go, idle); }); }
    };
    @if($gtmId)
    whenReady(function () {
        w.dataLayer = w.dataLayer || [];
        w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        var g = d.createElement('script'); g.async = true; g.src = 'https://www.googletagmanager.com/gtm.js?id={{ $gtmId }}';
        d.head.appendChild(g);
    }, 3000);
    @endif
    @if($clarityId)
    // Clarity later than GTM (10 s): it sets a cookie on clarity.ms, which PageSpeed's "Best
    // Practices" counts against the page whenever it loads inside the test. Real visitors scroll
    // or tap within seconds, so their sessions are recorded just the same.
    whenReady(function () {
        w.clarity = w.clarity || function () { (w.clarity.q = w.clarity.q || []).push(arguments); };
        var c = d.createElement('script'); c.async = true; c.src = 'https://www.clarity.ms/tag/{{ $clarityId }}';
        d.head.appendChild(c);
    }, 10000);
    @endif
    @if($gtmId)
    // Turbo swaps pages without a real browser load — tell GTM about each one, so tags can
    // trigger on "virtualPageview" (Custom Event trigger) as well as on the first load.
    d.addEventListener('turbo:load', function () {
        w.dataLayer = w.dataLayer || [];
        w.dataLayer.push({ event: 'virtualPageview', page_path: w.location.pathname + w.location.search, page_title: d.title });
    });
    @endif
})(window, document);
</script>
@endif
