{{-- Landing-page behavior tracker: scroll depth, active time, which "Order Now" button was
     tapped (data-lp-cta="hero|offer|sticky-bar"), whether the order form actually scrolled
     into view, and whether the visitor touched any of its fields. Sends to
     LandingPageTrackController — only after a real touch/mouse/wheel/key event, so
     JS-executing crawlers never count as a visit. Mirrors blog/partials/tracker.blade.php's
     shape exactly; see that file for the fuller explanation of the beacon pattern. --}}
@if($visitKey ?? null)
<script>
(function () {
    var TRACK_URL = @js(route('landing-pages.track')), FORM_URL = @js(route('landing-pages.track.reached-form')), PAGE = {{ (int) $landingPage->id }};
    if (!window.JSON || document.documentElement.hasAttribute('data-turbo-preview')) return;

    var uid = function () {
        return window.crypto && crypto.randomUUID ? crypto.randomUUID() : 'r' + Date.now().toString(36) + Math.random().toString(36).slice(2, 14);
    };
    var rk = @js($visitKey), rs = @js(\App\Models\LandingPageVisit::sign($visitKey, $landingPage->id)), vk;
    try { vk = localStorage.getItem('mv_lvk'); if (!vk) { vk = uid(); localStorage.setItem('mv_lvk', vk); } } catch (e) { vk = rk; }

    var human = false, shownAt = Date.now(), firstSent = false;
    var prevUrl = (window.mvCurUrl && window.mvCurUrl !== location.href ? window.mvCurUrl : document.referrer) || '';
    var active = 0, depth = 0, last = Date.now(), lastInput = 0, ticks = 0, sent = '', sentAt = 0, done = false, raf = 0;
    var formStarted = false, formReached = false;

    function send(extra, beacon) {
        if (!human) return;
        var p = { r: rk, k: rs, v: vk, p: PAGE, t: Math.round(active), s: depth };
        if (!firstSent) {
            firstSent = true;
            p.ref = prevUrl; p.q = location.search.slice(0, 900);
        }
        for (var k in extra) p[k] = extra[k];
        var body = JSON.stringify(p);
        if (beacon && navigator.sendBeacon) {
            try { if (navigator.sendBeacon(TRACK_URL, new Blob([body], { type: 'application/json' }))) return; } catch (e) {}
        }
        fetch(TRACK_URL, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: body }).catch(function () {});
    }
    function flush(beacon) {
        var state = Math.round(active) + ':' + depth;
        if (state === sent && (Date.now() - sentAt < 60000 || document.visibilityState !== 'visible')) return;
        sent = state; sentAt = Date.now();
        send({ e: 'ping' }, beacon);
    }

    function measure() {
        raf = 0;
        var doc = document.documentElement;
        var scrollable = Math.max(1, doc.scrollHeight - doc.clientHeight);
        var d = Math.max(0, Math.min(100, Math.round((window.scrollY || doc.scrollTop) / scrollable * 100)));
        if (d > depth) depth = d;
    }
    function onScroll() { if (human) lastInput = Date.now(); if (!raf) raf = requestAnimationFrame(measure); }
    function onInput(ev) {
        if (ev && ev.isTrusted === false) return;
        var now = Date.now();
        if (!human) {
            human = true;
            if (document.visibilityState === 'visible') active += Math.min(now - shownAt, 30000) / 1000;
            last = now;
            lastInput = now;
            send({ e: 'start' }, false);
            return;
        }
        lastInput = now;
    }

    // Order Now button taps — <a data-lp-cta="hero|offer|sticky-bar" href="#order-form">
    function onClick(ev) {
        var el = ev.target.closest ? ev.target.closest('[data-lp-cta]') : null;
        if (!el) return;
        send({ e: 'cta', pl: el.getAttribute('data-lp-cta') }, true);
    }

    // First real touch on an order-form field — a much stronger buy-intent signal than just
    // scrolling past it. Fires once; also fires the matching AddToCart pixel event right here
    // (eventID format must exactly match LandingPageTrackController::fireCapi()'s server side).
    function onFormInput(ev) {
        if (formStarted || (ev && ev.isTrusted === false)) return;
        var field = ev.target.closest ? ev.target.closest('input, select, textarea') : null;
        if (!field) return;
        formStarted = true;
        send({ e: 'form' }, true);
        if (typeof fbq === 'function') {
            var product = @json($trackProduct ?? null);
            fbq('track', 'AddToCart', product ? {
                content_ids: [product.id], content_type: 'product', value: product.value, currency: @js($trackCurrency ?? 'BDT'),
            } : {}, { eventID: 'form_start_' + rk });
        }
    }

    function onVisibility() { if (document.visibilityState === 'hidden') flush(true); else { last = lastInput = Date.now(); } }

    var inputs = ['pointerdown', 'keydown', 'touchstart', 'wheel', 'mousemove'];
    var timer = setInterval(function () {
        var now = Date.now();
        if (document.visibilityState === 'visible' && now - lastInput < 30000) active += Math.min(now - last, 2000) / 1000;
        last = now;
        if (++ticks % 15 === 0) flush(false);
    }, 1000);

    function stop() {
        if (done) return;
        done = true;
        flush(true);
        clearInterval(timer);
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
        inputs.forEach(function (t) { document.removeEventListener(t, onInput); });
        document.removeEventListener('click', onClick, true);
        document.removeEventListener('focusin', onFormInput);
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('pagehide', stop);
        document.removeEventListener('turbo:visit', stop);
        io && io.disconnect();
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    inputs.forEach(function (t) { document.addEventListener(t, onInput, { passive: true }); });
    document.addEventListener('click', onClick, true);
    document.addEventListener('focusin', onFormInput);
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('pagehide', stop);
    document.addEventListener('turbo:visit', stop);

    // Order form scrolled into view — records it for the funnel dashboard + fires the
    // server-side InitiateCheckout CAPI call (LandingPageTrackController::reachedForm, a
    // backup for ad-blocked browsers). The CLIENT pixel event for this same moment still
    // fires from the page's own trackInitiateCheckout() (show.blade.php /
    // themes/_order.blade.php) rather than here — that one has access to the live
    // Alpine-computed total (quantity tier + delivery charge already applied), which a
    // generic shared tracker can't know; both sides use the same 'reached_form_' + visit-key
    // eventID so Meta still dedupes the pair. Only once.
    var io = null, orderForm = document.getElementById('order-form');
    if (orderForm && 'IntersectionObserver' in window) {
        io = new IntersectionObserver(function (entries) {
            if (!entries[0].isIntersecting || formReached) return;
            formReached = true;
            io.disconnect();

            var body = JSON.stringify({ r: rk, k: rs, p: PAGE });
            if (navigator.sendBeacon) {
                try { if (navigator.sendBeacon(FORM_URL, new Blob([body], { type: 'application/json' }))) return; } catch (e) {}
            }
            fetch(FORM_URL, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: body }).catch(function () {});
        }, { threshold: 0.3 });
        io.observe(orderForm);
    }

    measure();
})();
</script>
@endif
