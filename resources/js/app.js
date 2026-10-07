import Alpine from 'alpinejs';
import * as Turbo from '@hotwired/turbo';
import './datepicker';
import './scroll-reveal';

window.Alpine = Alpine;
window.Turbo = Turbo;

// Registered once, unconditionally, here — not per-layout behind an alpine:init
// listener. Alpine.start() (and the one-time alpine:init event it fires) only
// ever runs once per Turbo session, on whichever page happened to load first;
// a store registered by a *different* layout's own inline script never exists
// at all if that layout's page wasn't the first one visited. The theme store's
// localStorage key comes from <body data-theme-key> instead of being hardcoded,
// since the storefront/guest/account layouts share 'site-theme' while the admin
// layout uses its own 'admin-theme' — read live (not captured once) so it's
// still correct after navigating between layouts.
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        const key = document.body.dataset.themeKey || 'site-theme';
        localStorage.setItem(key, this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

// Global (not local x-data) so the admin mobile bottom quick-nav — a sibling of
// the sidebar/topbar wrapper, outside its x-data scope — can also open the
// sidebar drawer via its "More" tab, not just the topbar hamburger button.
// Harmless on non-admin pages, where nothing reads it.
Alpine.store('adminSidebar', { open: false });

// Drives the mobile dynamic search bar (layouts/app.blade.php, right under the header) —
// global rather than a page-scoped x-init's own `window.addEventListener('scroll', ...)`
// on purpose: Turbo swaps <body> on every navigation, which tears down that page's Alpine
// component but does NOT remove a listener it registered directly on `window` — each
// navigation would leave one more stale listener behind, a leak that grows for as long as
// the tab stays open. Registered once here (module scripts never re-run under Turbo), with
// a single shared scroll listener below, exactly like the theme/adminSidebar stores above.
Alpine.store('mobileSearchBar', { visible: false });

Alpine.start();

// ── Turbo Drive compatibility shims ──────────────────────────────────────────
// Turbo intercepts every same-origin link click and form submit (unless marked
// data-turbo="false") and swaps <body> via fetch instead of a real browser
// navigation, so the whole site feels instant. A few things that assumption
// breaks, handled here once instead of touching every page that relies on them:

// 1. `DOMContentLoaded` only ever fires once per real page load — a few dozen
//    existing <script> blocks (admin dashboards' Chart.js setup, report pages,
//    the settings AJAX-save handler, etc.) use it to run their one-time setup.
//    Re-dispatching it on every turbo:load (after the first, which already had
//    a real DOMContentLoaded) means none of that code needs to change at all.
let firstTurboLoad = true;
document.addEventListener('turbo:load', () => {
    if (firstTurboLoad) {
        firstTurboLoad = false;
        return;
    }
    document.dispatchEvent(new Event('DOMContentLoaded'));
});

// 2. Each layout's own pre-paint <script> (in <head>, evaluated before first
//    paint to avoid a flash of the wrong theme) sets the <html class="dark">
//    directly from localStorage, bypassing the store above entirely — correct
//    for the very first page, and Turbo re-runs that inline script whenever a
//    *different* layout's head content loads (e.g. storefront -> admin), so the
//    class itself always ends up right. But the store's own `dark` boolean
//    doesn't know that happened, so anything bound to $store.theme.dark (the
//    sun/moon toggle icon) could show the wrong state after such a navigation
//    until this re-syncs it from the class that's now actually applied.
document.addEventListener('turbo:load', () => {
    Alpine.store('theme').dark = document.documentElement.classList.contains('dark');
});

// 3. The admin sidebar's mobile drawer-open state is global (see above), so —
//    unlike a page-scoped x-data — it survives a Turbo body swap untouched.
//    Without this, tapping a sidebar link while the drawer is open would leave
//    it stuck open on the page that loads next, since nothing else resets it.
document.addEventListener('turbo:load', () => {
    Alpine.store('adminSidebar').open = false;
});

// 4. One scroll listener for the whole Turbo session (see the store above) rather than
//    one per page. Resets to hidden on every navigation — scrollY often starts back at 0
//    on a fresh page anyway, but a same-page Turbo visit (e.g. a filter link) can land
//    with the browser preserving scroll position, and the bar popping in already-visible
//    before the customer has scrolled this page at all would look like a glitch.
let mobileSearchBarTicking = false;
window.addEventListener('scroll', () => {
    if (mobileSearchBarTicking) return;
    mobileSearchBarTicking = true;
    requestAnimationFrame(() => {
        Alpine.store('mobileSearchBar').visible = window.scrollY > 220;
        mobileSearchBarTicking = false;
    });
}, { passive: true });
document.addEventListener('turbo:load', () => {
    Alpine.store('mobileSearchBar').visible = false;
});
