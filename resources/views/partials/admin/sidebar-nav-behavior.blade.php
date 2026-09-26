{{-- Sidebar behavior: collapsible groups, remembered scroll position, and "always show me where I
     am". Runs synchronously right after the sidebar markup and BEFORE it is first shown (the nav
     is visibility:hidden via data-nav-init="pending" until then), so nothing jumps or flashes.
     Deliberately plain JS, not Alpine: it has to finish before Alpine even boots. --}}
<noscript><style>[data-admin-nav][data-nav-init="pending"] { visibility: visible; }</style></noscript>
<script>
(function () {
    var nav = document.querySelector('[data-admin-nav]');
    if (!nav) return;

    try {
        var OPEN_KEY = 'adminNavOpen';      // localStorage: groups the admin opened by hand (survive across visits)
        var SCROLL_KEY = 'adminNavScroll';  // sessionStorage: sidebar scroll position (per tab)

        var groups = [].slice.call(nav.querySelectorAll('[data-nav-group]'));
        var store = function (area, key, value) {
            try { window[area].setItem(key, JSON.stringify(value)); } catch (e) { /* private mode / quota: just don't persist */ }
        };
        var load = function (area, key) {
            try { return JSON.parse(window[area].getItem(key)); } catch (e) { return null; }
        };
        var groupName = function (g) { return g.getAttribute('data-nav-group'); };

        var pinned = load('localStorage', OPEN_KEY);
        if (!Array.isArray(pinned)) pinned = [];

        var setOpen = function (group, open) {
            group.setAttribute('data-open', open ? '1' : '0');
            var head = group.querySelector('.admin-nav-group__head');
            if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        // The group holding the current page is always open; otherwise only groups pinned open by hand.
        groups.forEach(function (g) {
            setOpen(g, g.getAttribute('data-has-active') === '1' || pinned.indexOf(groupName(g)) !== -1);
        });

        // Mark the current page for assistive tech, and remember the first match for scrolling to it.
        var active = null;
        [].slice.call(nav.querySelectorAll('a')).forEach(function (a) {
            var c = ' ' + a.className + ' ';
            if ((c.indexOf(' bg-orange-600 ') !== -1 || c.indexOf(' bg-red-700 ') !== -1) && c.indexOf(' text-white ') !== -1) {
                a.setAttribute('aria-current', 'page');
                if (!active) active = a;
            }
        });

        // Scroll: put the sidebar back where it was on the previous page, then — if the current
        // page's link still isn't comfortably in view (first visit, deep link, a group that just
        // opened) — centre it. This is what stops the "scroll all the way down again on every click".
        var scroller = nav;
        var saved = parseInt(sessionStorage.getItem(SCROLL_KEY), 10);
        if (!isNaN(saved)) scroller.scrollTop = saved;
        if (active) {
            var nr = scroller.getBoundingClientRect();
            var ar = active.getBoundingClientRect();
            var margin = 56; // clear of the sticky toolbar at the top and the edge at the bottom
            if (ar.top < nr.top + margin || ar.bottom > nr.bottom - margin) {
                scroller.scrollTop += (ar.top - nr.top) - (nr.height - ar.height) / 2;
            }
        }

        var raf = null;
        var saveScroll = function () { store('sessionStorage', SCROLL_KEY, scroller.scrollTop); };
        scroller.addEventListener('scroll', function () {
            if (raf) return;
            raf = requestAnimationFrame(function () { raf = null; saveScroll(); });
        }, { passive: true });
        window.addEventListener('pagehide', saveScroll);

        // Only what the admin explicitly opened/closed is remembered. A group that is open merely
        // because it holds the current page is NOT pinned — otherwise every section you ever
        // visited would stay open forever and the sidebar would slowly turn back into one long list.
        var pin = function () { store('localStorage', OPEN_KEY, pinned); };

        nav.addEventListener('click', function (event) {
            var head = event.target.closest('.admin-nav-group__head');
            if (head) {
                var group = head.closest('[data-nav-group]');
                var open = group.getAttribute('data-open') !== '1';
                setOpen(group, open);
                var name = groupName(group);
                pinned = pinned.filter(function (n) { return n !== name; });
                if (open) pinned.push(name);
                pin();
                return;
            }
            if (event.target.closest('[data-nav-expand-all]')) {
                groups.forEach(function (g) { setOpen(g, true); });
                pinned = groups.map(groupName);
                pin();
            } else if (event.target.closest('[data-nav-collapse-all]')) {
                // Everything except the section you're currently in, so you never lose your place.
                groups.forEach(function (g) { setOpen(g, g.getAttribute('data-has-active') === '1'); });
                pinned = [];
                pin();
            }
        });
    } finally {
        // Whatever happened above, never leave the navigation hidden.
        nav.setAttribute('data-nav-init', 'done');
        requestAnimationFrame(function () { requestAnimationFrame(function () { nav.setAttribute('data-nav-ready', '1'); }); });
    }
})();
</script>
