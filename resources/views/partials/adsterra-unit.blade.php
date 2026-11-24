{{-- One Adsterra ad placement — include with:
     @include('partials.adsterra-unit', ['code' => setting('adsterra_code_infeed')])
     Unlike AdSense (one stable data-ad-client/data-ad-slot shape), Adsterra's snippet differs
     per ad format (Banner/Native/Social Bar/etc.), so this renders the admin's pasted code
     verbatim rather than assuming any particular structure — same trust model as the site's
     Custom CSS/JS fields (admin-only, not sanitized, since sanitizing would just strip the
     script tags the ad needs). Blog-only by placement (see where this is included), same as
     the AdSense unit.

     Backup: Adsterra sometimes returns nothing for a unit (live: the 728x90 article and 320x50
     sidebar units came back empty while the 300x250 one filled). A slot that is still empty a
     few seconds after the page loads runs the Backup code (Settings → Advertisements, else the
     In-Feed code) instead, and a slot that stays empty is hidden so no bare "Advertisement"
     label is left behind. --}}
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
        {!! $code !!}
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
        var check = function () {
            fit();
            var queue = [].filter.call(document.querySelectorAll('[data-adsterra]'), function (s) { return !s.dataset.adChecked; });
            (function nextSlot() {
                var slot = queue.shift();
                if (!slot) return;
                slot.dataset.adChecked = '1';
                if (filled(slot)) return nextSlot();
                var backup = slot.querySelector('template[data-adsterra-backup]');
                if (!backup) { slot.hidden = true; return nextSlot(); }
                var html = backup.innerHTML; backup.remove();
                run(slot, html, function () {
                    setTimeout(function () { if (!filled(slot)) slot.hidden = true; else fit(); }, 4000);
                    setTimeout(nextSlot, 1200); // one unit at a time: Adsterra reads one global atOptions
                });
            })();
        };
        var start = function () {
            fit();
            if (window.MutationObserver) { // banners that arrive later are fitted the moment they land
                var mo = new MutationObserver(function () { clearTimeout(timer); timer = setTimeout(fit, 50); });
                [].forEach.call(document.querySelectorAll('[data-adsterra]'), function (s) { mo.observe(s, { childList: true }); });
            }
            setTimeout(check, 3500);
        };
        document.readyState === 'complete' ? start() : window.addEventListener('load', start, { once: true });
    })();
    </script>
    @endonce
@endif
