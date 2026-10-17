{{-- Google Tag Manager + Microsoft Clarity (Settings → Google Analytics & Ads), each with its
     own on/off switch. Shared by the storefront and landing-page layouts.

     Both load only once the page has finished loading (+3 s) or on the visitor's first
     scroll/tap/keypress, whichever comes first — the same approach as the Facebook Pixel — so
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
    var started = false;
    var start = function () {
        if (started) return;
        started = true;
        ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { w.removeEventListener(ev, start); });
        @if($gtmId)
        w.dataLayer = w.dataLayer || [];
        w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        var g = d.createElement('script'); g.async = true; g.src = 'https://www.googletagmanager.com/gtm.js?id={{ $gtmId }}';
        d.head.appendChild(g);
        @endif
        @if($clarityId)
        w.clarity = w.clarity || function () { (w.clarity.q = w.clarity.q || []).push(arguments); };
        var c = d.createElement('script'); c.async = true; c.src = 'https://www.clarity.ms/tag/{{ $clarityId }}';
        d.head.appendChild(c);
        @endif
    };
    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { w.addEventListener(ev, start, { passive: true }); });
    if (d.readyState === 'complete') { setTimeout(start, 3000); } else { w.addEventListener('load', function () { setTimeout(start, 3000); }); }
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
