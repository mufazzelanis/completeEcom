{{-- Article reading tracker + reading progress bar + sticky "in this article" buy bar.
     Sends to BlogTrackController — only after the first real touch / mouse / wheel / key event, so
     script-running crawlers never count: start (with the real referrer and landing query), a ping every
     ~15 s while the reader is active (tab visible, input in the last 30 s) and when they leave,
     and a click for every product action inside [data-bp] cards. Plain JS, passive listeners and
     one rAF per scroll frame; nothing is fetched up front, so it adds no work to the page load.
     Re-runs on every Turbo visit and tears itself down when the visitor moves on. --}}
@once
<style>
    .bshop-card { transition: box-shadow .2s ease, transform .2s ease; }
    .bshop-card:hover { box-shadow: 0 14px 30px -18px rgba(15, 23, 42, .45); }
    .bshop-track { scroll-snap-type: x mandatory; scroll-padding-inline: .75rem; scrollbar-width: none; }
    .bshop-track::-webkit-scrollbar { display: none; }
    .bshop-track > * { scroll-snap-align: start; }
    #blog-progress { position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 60; transform-origin: 0 50%; transform: scaleX(0); pointer-events: none; }

    .bsb { position: fixed; left: 0; right: 0; bottom: calc(5rem + env(safe-area-inset-bottom, 0px)); z-index: 40; padding: 0 .75rem; transform: translateY(140%); opacity: 0; transition: transform .28s ease, opacity .28s ease; pointer-events: none; }
    .bsb.is-on { transform: none; opacity: 1; pointer-events: auto; }
    @media (min-width: 768px) { .bsb { bottom: 1.5rem; } }
    .bsb-in { position: relative; max-width: 30rem; margin: 0 auto; display: flex; align-items: center; gap: .6rem; padding: .45rem; border-radius: 1.1rem; background: rgba(255, 255, 255, .97); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); box-shadow: 0 14px 34px -10px rgba(15, 23, 42, .38); border: 1px solid rgba(15, 23, 42, .07); }
    .dark .bsb-in { background: rgba(17, 24, 39, .96); border-color: rgba(255, 255, 255, .08); }
    .bsb-img { position: relative; width: 2.9rem; height: 2.9rem; border-radius: .75rem; overflow: hidden; background: #f3f4f6; flex-shrink: 0; }
    .dark .bsb-img { background: #1f2937; }
    .bsb-x { position: absolute; top: -.55rem; right: -.35rem; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; background: #fff; color: #6b7280; box-shadow: 0 2px 8px rgba(15, 23, 42, .2); }
    .dark .bsb-x { background: #374151; color: #d1d5db; }
    @media (max-width: 767px) {
        body .fixed.bottom-20 { transition: bottom .28s ease; }
        body.bsb-on .fixed.bottom-20 { bottom: calc(9.5rem + env(safe-area-inset-bottom, 0px)); }
    }
    @media (prefers-reduced-motion: reduce) { .bsb, .bshop-card { transition: none; } }
</style>
@endonce

<div id="blog-progress" class="bg-orange-500" aria-hidden="true"></div>

@if($primaryProduct)
    @php
        $sbFinal = (float) $primaryProduct->final_price;
        $sbBase = (float) $primaryProduct->price;
    @endphp
    <div id="blog-sb" class="bsb" hidden aria-hidden="true">
        <div class="bsb-in" data-bp="{{ $primaryProduct->id }}" data-bp-place="sticky" data-track-item="{{ json_encode(tracking_item($primaryProduct), JSON_UNESCAPED_UNICODE) }}">
            <a href="{{ route('products.show', $primaryProduct->slug) }}" class="bsb-img" tabindex="-1">
                @if($primaryProduct->image)
                    <img src="{{ image_thumb($primaryProduct->image, 200) }}" alt="" width="200" height="200" loading="lazy" class="absolute inset-0 w-full h-full p-1 object-contain text-[0px]">
                @endif
            </a>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400 leading-none">In this article</p>
                <a href="{{ route('products.show', $primaryProduct->slug) }}" class="block mt-0.5 text-[13px] font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $primaryProduct->name }}</a>
                <p class="text-sm font-extrabold text-gray-900 dark:text-white leading-tight">৳{{ number_format($sbFinal) }}@if($sbBase > $sbFinal) <s class="ml-0.5 text-[11px] font-medium text-gray-400">৳{{ number_format($sbBase) }}</s>@endif</p>
            </div>
            @include('blog.partials.buy', ['product' => $primaryProduct, 'size' => 'sm', 'class' => 'flex-shrink-0 w-[8.5rem] sm:w-40'])
            <button type="button" class="bsb-x" data-bsb-close aria-label="Hide">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
@endif

<script>
(function () {
    var URL_ = @js(route('blog.track')), POST = {{ (int) $blogPost->id }};
    var article = document.querySelector('[data-blog-body]');
    // a Turbo preview (cached copy shown while the real page loads) runs this too — only the real render counts
    if (!article || !window.JSON || document.documentElement.hasAttribute('data-turbo-preview')) return;

    var uid = function () {
        return window.crypto && crypto.randomUUID ? crypto.randomUUID() : 'r' + Date.now().toString(36) + Math.random().toString(36).slice(2, 14);
    };
    // the server already recorded this view (BlogRead::begin) — continue that row; none for staff
    var rk = @js($readKey ?? null), rs = @js(isset($readKey) ? \App\Models\BlogRead::sign($readKey, $blogPost->id) : null), vk;
    var off = rk === null && @js(auth()->check() && auth()->user()->canAccessAdmin()); // staff: page works, nothing is counted
    rk = rk || uid();
    try { vk = localStorage.getItem('mv_bvk'); if (!vk) { vk = uid(); localStorage.setItem('mv_bvk', vk); } } catch (e) { vk = rk; }

    // Nothing is sent until a person shows up: a real touch / mouse / wheel / key event (isTrusted).
    // Crawlers that run JavaScript (Googlebot's renderer, headless Chrome on cloud servers) load and
    // scroll pages but don't produce those, so they never become a "reader" with reading time.
    var human = false, shownAt = Date.now(), firstSent = false;
    // read now: once turbo:load has run, window.mvCurUrl is this page, not the one before it
    var prevUrl = (window.mvCurUrl && window.mvCurUrl !== location.href ? window.mvCurUrl : document.referrer) || '';
    var active = 0, depth = 0, last = Date.now(), lastInput = 0, ticks = 0, sent = '', sentAt = 0, done = false, raf = 0;
    var progress = document.getElementById('blog-progress');
    var sb = document.getElementById('blog-sb'), sbClosed = false, blocksInView = 0, io = null;
    try { sbClosed = sessionStorage.getItem('bsb-x-' + POST) === '1'; } catch (e) {}

    function send(extra, beacon) {
        if (!human || off || !rs) return;
        var p = { r: rk, k: rs, v: vk, p: POST, t: Math.round(active), s: depth };
        if (!firstSent) {
            // the first message creates the read on the server: say where the reader came from
            firstSent = true;
            p.ref = prevUrl; p.q = location.search.slice(0, 900);
        }
        for (var k in extra) p[k] = extra[k];
        var body = JSON.stringify(p);
        if (beacon && navigator.sendBeacon) {
            try { if (navigator.sendBeacon(URL_, new Blob([body], { type: 'application/json' }))) return; } catch (e) {}
        }
        fetch(URL_, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: body }).catch(function () {});
    }
    function flush(beacon) {
        var state = Math.round(active) + ':' + depth;
        // nothing new: skip — except a once-a-minute keep-alive while the tab is actually in front
        // (keeps "reading now" right without counting a forgotten background tab)
        if (state === sent && (Date.now() - sentAt < 60000 || document.visibilityState !== 'visible')) return;
        sent = state; sentAt = Date.now();
        send({ e: 'ping' }, beacon);
    }

    function setBar(on) {
        if (!sb) return;
        sb.classList.toggle('is-on', on);
        sb.setAttribute('aria-hidden', on ? 'false' : 'true');
        document.body.classList.toggle('bsb-on', on);
    }
    function measure() {
        raf = 0;
        var r = article.getBoundingClientRect(), vh = window.innerHeight, h = Math.max(1, r.height);
        var d = r.top >= vh ? 0 : Math.max(0, Math.min(100, Math.round((vh - r.top) / h * 100)));
        if (d > depth) depth = d;
        if (progress) progress.style.transform = 'scaleX(' + Math.max(0, Math.min(1, -r.top / Math.max(1, h - vh))) + ')';
        // only while reading the text itself, and never on top of a product block that's already on screen
        setBar(!sbClosed && blocksInView === 0 && r.top < -240 && r.bottom > vh * .6);
    }
    function onScroll() { if (human) lastInput = Date.now(); if (!raf) raf = requestAnimationFrame(measure); }
    function onInput(ev) {
        if (ev && ev.isTrusted === false) return;
        var now = Date.now();
        if (!human) {
            human = true;
            // credit the time spent reading the opening before the first touch/scroll (at most 30 s)
            if (document.visibilityState === 'visible') active += Math.min(now - shownAt, 30000) / 1000;
            last = now;
            lastInput = now;
            send({ e: 'start' }, false);
            return;
        }
        lastInput = now;
    }

    function onClick(ev) {
        var el = ev.target.closest ? ev.target.closest('a, button') : null;
        var card = el && el.closest('[data-bp]');
        if (!card) return;
        if (el.hasAttribute('data-bsb-close')) {
            sbClosed = true; setBar(false);
            try { sessionStorage.setItem('bsb-x-' + POST, '1'); } catch (e) {}
            return;
        }
        if (el.tagName === 'BUTTON' && !el.hasAttribute('data-bp-act')) return;
        var act = el.getAttribute('data-bp-act') || 'open';
        if (act === 'cart' && el.getAttribute('data-in-cart') === 'true') return; // taking it out again
        send({ e: 'click', pid: +card.getAttribute('data-bp'), pl: card.getAttribute('data-bp-place') || 'inline', a: act }, true);
        // order attribution (BlogOrderObserver): the next order within 7 days credits this post
        document.cookie = 'blg=' + POST + '.' + rk + ';max-age=604800;path=/;samesite=lax' + (location.protocol === 'https:' ? ';secure' : '');
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
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('pagehide', stop);
        document.removeEventListener('turbo:visit', stop);
        io && io.disconnect();
        setBar(false);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    inputs.forEach(function (t) { document.addEventListener(t, onInput, { passive: true }); });
    document.addEventListener('click', onClick, true);
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('pagehide', stop);
    document.addEventListener('turbo:visit', stop);

    if (sb) {
        document.body.appendChild(sb); // a transformed ancestor would trap position:fixed
        sb.hidden = false;
        if ('IntersectionObserver' in window) {
            io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { blocksInView += e.isIntersecting ? 1 : (e.target.__bIn ? -1 : 0); e.target.__bIn = e.isIntersecting; });
                blocksInView = Math.max(0, blocksInView);
                onScroll();
            });
            document.querySelectorAll('[data-bshop-block], #blog-shop').forEach(function (el) { io.observe(el); });
        }
    }

    measure();
})();
</script>
