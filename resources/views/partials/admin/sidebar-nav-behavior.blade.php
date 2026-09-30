{{-- Sidebar behavior: collapsible groups, remembered scroll position, and "always show me where I
     am". Runs synchronously right after the sidebar markup and BEFORE it is first shown (the nav
     is visibility:hidden via data-nav-init="pending" until then), so nothing jumps or flashes.
     Deliberately plain JS, not Alpine: it has to finish before Alpine even boots. --}}
<noscript><style>[data-admin-nav][data-nav-init="pending"] { visibility: visible; }</style></noscript>
<script>
(function () {
    var nav = document.querySelector('[data-admin-nav]');
    if (!nav) return;
    var sidebar = document.getElementById('admin-sidebar');

    try {
        var OPEN_KEY = 'adminNavOpen';        // localStorage: groups the admin opened by hand (survive across visits)
        var SCROLL_KEY = 'adminNavScroll';    // sessionStorage: sidebar scroll position (per tab)
        var COLLAPSED_KEY = 'adminSidebarCollapsed'; // localStorage: whole-sidebar icon-rail mode (desktop only)

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

        // Icon-rail collapse — applied up front, same as the group open/closed state above, so
        // a returning admin who collapsed the sidebar never sees it flash full-width first.
        // Pure CSS (@media (min-width:1024px) in app.css) makes this a no-op on mobile.
        var collapseToggle = sidebar ? sidebar.querySelector('[data-sidebar-collapse-toggle]') : null;
        var setCollapsed = function (collapsed) {
            if (!sidebar) return;
            sidebar.setAttribute('data-collapsed', collapsed ? '1' : '0');
            if (collapseToggle) {
                collapseToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
                collapseToggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
            }
        };
        setCollapsed(load('localStorage', COLLAPSED_KEY) === true);
        if (collapseToggle) {
            collapseToggle.addEventListener('click', function () {
                var next = sidebar.getAttribute('data-collapsed') !== '1';
                setCollapsed(next);
                store('localStorage', COLLAPSED_KEY, next);
            });
        }

        var setOpen = function (group, open) {
            group.setAttribute('data-open', open ? '1' : '0');
            var head = group.querySelector('.admin-nav-group__head');
            if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        // Icon-rail flyouts (app.css: `#admin-sidebar[data-collapsed="1"] .admin-nav-group__body`)
        // are `position: fixed` so <nav>'s own overflow-y:auto (needed for its much-taller-than-
        // the-sidebar link list) can't clip their width — but that means CSS alone can't place
        // one under its group, since the group's on-screen position depends on how far the admin
        // has scrolled the nav. Recomputed on every hover/focus rather than once, so scrolling
        // the sidebar between visits (or resizing the window) never leaves a flyout stranded next
        // to the wrong icon.
        var positionFlyout = function (group) {
            if (!sidebar || sidebar.getAttribute('data-collapsed') !== '1') return;
            var body = group.querySelector('.admin-nav-group__body');
            if (!body) return;
            var groupRect = group.getBoundingClientRect();
            var asideRect = sidebar.getBoundingClientRect();
            body.style.top = Math.max(4, groupRect.top - asideRect.top - 4) + 'px';
        };

        // [data-flyout-open] state machine (app.css reads it, not :hover) — see the CSS
        // comment above .admin-nav-group__body for why: the rail is a contiguous vertical
        // strip of icons, so reaching for a low item in a tall flyout sweeps the mouse
        // across other icons on the way there. OPEN_DELAY means a brief graze across one
        // doesn't switch to it; CLOSE_DELAY means briefly leaving the current one (to
        // physically travel to its own flyout) doesn't hide it first.
        var OPEN_DELAY = 150;
        var CLOSE_DELAY = 300;
        var openTimer = null;
        var closeTimers = {}; // one per group, since more than one can be mid-close at once
        var openGroup = null;

        var reallyOpen = function (group) {
            if (openGroup && openGroup !== group) openGroup.removeAttribute('data-flyout-open');
            positionFlyout(group);
            group.setAttribute('data-flyout-open', '1');
            openGroup = group;
        };
        var reallyClose = function (group) {
            group.removeAttribute('data-flyout-open');
            if (openGroup === group) openGroup = null;
        };

        groups.forEach(function (g) {
            g.addEventListener('mouseenter', function () {
                if (!sidebar || sidebar.getAttribute('data-collapsed') !== '1') return;
                if (closeTimers[groupName(g)]) { clearTimeout(closeTimers[groupName(g)]); delete closeTimers[groupName(g)]; }
                if (openGroup === g) return; // already open — just cancelled its pending close above
                if (openTimer) clearTimeout(openTimer);
                openTimer = setTimeout(function () { openTimer = null; reallyOpen(g); }, OPEN_DELAY);
            });
            g.addEventListener('mouseleave', function () {
                if (!sidebar || sidebar.getAttribute('data-collapsed') !== '1') return;
                if (openTimer) { clearTimeout(openTimer); openTimer = null; }
                var name = groupName(g);
                closeTimers[name] = setTimeout(function () { delete closeTimers[name]; reallyClose(g); }, CLOSE_DELAY);
            });
        });
        // Keyboard focus stays instant (CSS :focus-within already shows it with no delay)
        // — this only needs to keep `top` correct and the JS state in sync so a mouse
        // move afterward doesn't fight with what Tab just opened.
        nav.addEventListener('focusin', function (event) {
            var group = event.target.closest('[data-nav-group]');
            if (!group) return;
            positionFlyout(group);
            if (openTimer) { clearTimeout(openTimer); openTimer = null; }
            var name = groupName(group);
            if (closeTimers[name]) { clearTimeout(closeTimers[name]); delete closeTimers[name]; }
            reallyOpen(group);
        });

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
                // In the icon rail, hovering/focusing the head alone reveals its flyout (pure CSS
                // — see app.css); a click here is just how that same reveal happens on a
                // touchscreen with no hover, so it must not also silently pin/unpin this group's
                // open state for whenever the rail expands back to normal.
                if (sidebar && sidebar.getAttribute('data-collapsed') === '1') {
                    return;
                }
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
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                nav.setAttribute('data-nav-ready', '1');
                // Belt-and-suspenders alongside the parse-blocking script itself: the width
                // transition (app.css) only turns on after this point, so even if a browser
                // somehow painted a frame before this script ran, that first frame couldn't
                // have animated.
                if (sidebar) sidebar.setAttribute('data-collapse-ready', '1');
            });
        });
    }
})();
</script>
