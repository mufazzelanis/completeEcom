{{-- One Adsterra ad placement — include with:
     @include('partials.adsterra-unit', ['code' => setting('adsterra_code_infeed')])
     Unlike AdSense (one stable data-ad-client/data-ad-slot shape), Adsterra's snippet differs
     per ad format (Banner/Native/Social Bar/etc.), so this renders the admin's pasted code
     verbatim rather than assuming any particular structure — same trust model as the site's
     Custom CSS/JS fields (admin-only, not sanitized, since sanitizing would just strip the
     script tags the ad needs). Blog-only by placement (see where this is included), same as
     the AdSense unit.

     Lazy: the code sits in a <template> and runs only after the page has loaded and once its
     slot is near the screen (live: running every ad's scripts during load kept a blog post's
     main thread busy for 3+ s on a phone). Readers still see each ad as they scroll to it.

     Backup: Adsterra sometimes returns nothing for a unit (live: the 728x90 article and 320x50
     sidebar units came back empty while the 300x250 one filled). A slot still empty a few
     seconds after its ad ran gets the Backup code (Settings → Advertisements, else the In-Feed
     code), and a slot that stays empty is hidden so no bare "Advertisement" label is left. --}}
@php
    $adsterraEnabled = setting('adsterra_enabled', '0') === '1';
    $adsterraBackup = trim((string) (setting('adsterra_code_fallback') ?: setting('adsterra_code_infeed', '')));
    $adsterraBackup = $adsterraBackup !== '' && $adsterraBackup !== trim((string) $code) ? $adsterraBackup : '';
@endphp
@if($adsterraEnabled && !empty($code))
    {{-- overflow-hidden + the adsterra-ad-slot CSS in app.css (max-width:100% on any child
         iframe/div/ins) — the admin's pasted code can be a fixed-width creative (a 728x90
         banner, say) that's wider than a phone screen; without this it breaks out of the
         column and forces the whole page to scroll sideways instead of just scaling down. --}}
    <div class="adsterra-ad-slot my-5 text-center overflow-hidden" data-adsterra>
        <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-600 mb-1.5">Advertisement</p>
        <template data-adsterra-code>{!! $code !!}</template>
        @if($adsterraBackup)<template data-adsterra-backup>{!! $adsterraBackup !!}</template>@endif
    </div>
    @once
    <script>
    (function () {
        // filled = the network drew something (iframe / image / its own markup), not just our label + its scripts
        var filled = function (s) { return !!s.querySelector('iframe, img, ins, a') || [].some.call(s.children, function (c) { return !/^(P|SCRIPT|TEMPLATE|NOSCRIPT)$/.test(c.tagName) && c.offsetHeight > 10; }); };
        // run a snippet's nodes in order, each <script src> waited for, so each unit's atOptions is read by its own invoke.js
        var run = function (slot, html, done) {
            var t = document.createElement('template'); t.innerHTML = html;
            var nodes = [].slice.call(t.content.childNodes), i = 0;
            (function next() {
                if (i >= nodes.length) return done();
                var n = nodes[i++];
                if (n.nodeName !== 'SCRIPT') { slot.appendChild(n); return next(); }
                var s = document.createElement('script');
                [].forEach.call(n.attributes, function (a) { s.setAttribute(a.name, a.value); });
                if (n.src) { s.onload = s.onerror = next; slot.appendChild(s); }
                else { s.text = n.text; slot.appendChild(s); next(); }
            })();
        };
        // one snippet at a time across the page: Adsterra reads a single global atOptions
        var jobs = [], busy = false;
        var pump = function () {
            if (busy || !jobs.length) return;
            busy = true;
            var job = jobs.shift();
            run(job.slot, job.html, function () { setTimeout(function () { busy = false; pump(); }, 1200); job.after && job.after(); });
        };
        var take = function (slot, sel) { var t = slot.querySelector('template[' + sel + ']'); if (!t) return null; var h = t.innerHTML; t.remove(); return h; };
        // a fixed-size banner wider than its column (728x90 on a phone, 320x50 in the 288px sidebar)
        // is shrunk to fit whole, instead of being cut off at the column edge
        var fit = function () {
            [].forEach.call(document.querySelectorAll('[data-adsterra] iframe'), function (f) {
                if (!f.title) f.title = 'Advertisement'; // screen readers name the frame
                var w = parseInt(f.getAttribute('width'), 10), h = parseInt(f.getAttribute('height'), 10);
                var room = f.parentNode.clientWidth;
                if (!w || !h || !room) return;
                var s = Math.min(1, room / w);
                f.style.cssText += ';display:block;max-width:none;width:' + w + 'px;height:' + h + 'px;transform-origin:0 0;transform:scale(' + s + ')'
                    + ';margin:0 0 ' + (-h * (1 - s)) + 'px ' + Math.max(0, (room - w * s) / 2) + 'px';
            });
        };
        var timer; window.addEventListener('resize', function () { clearTimeout(timer); timer = setTimeout(fit, 200); });
        var mo = window.MutationObserver ? new MutationObserver(function () { clearTimeout(timer); timer = setTimeout(fit, 50); }) : null;

        // a slot's own ad, then (if Adsterra sent nothing) its backup, then hide it if still empty
        var show = function (slot) {
            if (slot.dataset.adStarted) return;
            slot.dataset.adStarted = '1';
            mo && mo.observe(slot, { childList: true });
            var html = take(slot, 'data-adsterra-code');
            if (!html) return;
            jobs.push({ slot: slot, html: html, after: function () {
                setTimeout(function () {
                    if (filled(slot)) return fit();
                    var backup = take(slot, 'data-adsterra-backup');
                    if (!backup) { slot.hidden = true; return; }
                    jobs.push({ slot: slot, html: backup, after: function () { setTimeout(function () { filled(slot) ? fit() : (slot.hidden = true); }, 4000); } });
                    pump();
                }, 4500);
            } });
            pump();
        };
        var start = function () {
            var slots = [].slice.call(document.querySelectorAll('[data-adsterra]'));
            if (!('IntersectionObserver' in window)) return slots.forEach(show);
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) { if (e.isIntersecting) { io.unobserve(e.target); show(e.target); } });
            }, { rootMargin: '600px 0px' }); // start a little before it scrolls into view
            slots.forEach(function (s) { io.observe(s); });
        };
        // after the page itself is up (and the browser is idle) — never competing with the article
        var later = function () { 'requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 2500 }) : setTimeout(start, 1200); };
        document.readyState === 'complete' ? later() : window.addEventListener('load', later, { once: true });
    })();
    </script>
    @endonce
@endif
