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
//    Each Turbo page's inline scripts re-run and register their handler again, so
//    re-dispatching the real event would also re-run every handler from every page
//    visited before (one more copy per navigation — the panel got slower the longer
//    it stayed open). Instead, handlers registered after the real load are queued for
//    the page that registered them and run once on its turbo:load, then dropped.
//    Module code (datepicker, scroll-reveal) hooks turbo:load itself.
let realDomReady = document.readyState === 'complete';
document.addEventListener('DOMContentLoaded', () => { realDomReady = true; }, { once: true });
const nativeAddListener = document.addEventListener;
let pageReadyQueue = [];
document.addEventListener = function (type, listener, options) {
    if (type === 'DOMContentLoaded' && realDomReady && this === document) {
        pageReadyQueue.push(listener);
        return;
    }
    return nativeAddListener.call(this, type, listener, options);
};
document.addEventListener('turbo:load', () => {
    const queue = pageReadyQueue;
    pageReadyQueue = [];
    const event = new Event('DOMContentLoaded');
    queue.forEach((listener) => {
        try { typeof listener === 'function' ? listener.call(document, event) : listener.handleEvent(event); }
        catch (e) { console.error(e); }
    });
});

// 1b. x-teleport'd panels (header mega-menus, dropdowns) sit directly under <body>, away from
//     the component they belong to. Turbo's page-cache snapshot would keep copies of them
//     without that component, and showing the snapshot (back to the home page, etc.) made
//     Alpine evaluate them scope-less — a burst of "panel / top / seen is not defined"
//     errors. They are rebuilt from the template on render anyway, so drop them first.
document.addEventListener('turbo:before-cache', () => {
    [...document.body.children].forEach((el) => { if (el._x_teleportBack) el.remove(); });
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

// 5. The mobile search bar (layouts/app.blade.php) is `position: sticky; top: var(--header-
//    height)` so it pins directly below <header> instead of underneath it — two sibling
//    sticky elements both sitting at `top: 0` don't auto-stack, they just occupy the exact
//    same spot, and since <header> has the higher z-index it was silently winning there,
//    hiding the bar completely even once its `visible` store flag was true. <header>'s own
//    height isn't a fixed number (announcement bar/top bar toggle independently, fonts and
//    admin-configured padding vary it further), so it's measured and kept in sync via
//    ResizeObserver rather than hard-coded. Re-targeted on every turbo:load (not just once)
//    because Turbo replaces <body> wholesale on navigation — an observer left watching the
//    old (now-detached) <header> node would silently stop updating after the first visit.
let headerResizeObserver = null;
function observeHeaderHeight() {
    const headerEl = document.querySelector('header');
    if (!headerEl || !('ResizeObserver' in window)) return;
    headerResizeObserver?.disconnect();
    headerResizeObserver = new ResizeObserver(() => {
        document.documentElement.style.setProperty('--header-height', `${headerEl.offsetHeight}px`);
    });
    headerResizeObserver.observe(headerEl);
}
observeHeaderHeight();
document.addEventListener('turbo:load', observeHeaderHeight);

// 6. Feedback the moment a link is tapped. Turbo's own progress bar only appears after 500ms,
//    so on a slow connection a tap looked like it did nothing for half a second — shown after
//    120ms now, and the tapped link itself dims until the next page is on screen.
Turbo.setProgressBarDelay(120);
let pendingLink = null;
const clearPendingLink = () => { pendingLink?.classList.remove('turbo-pending'); pendingLink = null; };
document.addEventListener('turbo:click', (e) => {
    clearPendingLink();
    pendingLink = e.target.closest?.('a') || null;
    pendingLink?.classList.add('turbo-pending');
    setTimeout(clearPendingLink, 8000); // a visit cancelled elsewhere never leaves it dimmed
});
['turbo:before-cache', 'turbo:load', 'turbo:fetch-request-error'].forEach((t) => document.addEventListener(t, clearPendingLink));

// 7. Turbo prefetches a link's page as soon as the mouse rests on it (desktop), so the click
//    itself is near-instant. Not for pages where merely loading them does work: checkout
//    (InitiateCheckout event), cart, logout, file downloads/exports/invoices (heavy to build),
//    the notifications page and admin alert "open" links (mark things read), template seeding,
//    social-login redirects, unsubscribe links, vendor document files. Product/blog/landing
//    views are safe — the server defers their tracking until the page is actually shown
//    (App\Support\PageView).
const NO_PREFETCH = /\/(checkout|cart|buy-now|logout|sync|pv|auth|unsubscribe|document)(\/|$)|\/(download|export|invoice|pdf|print|label)s?(\/|$)|\/(notifications|open|seed)$|\.(pdf|csv|xlsx?|zip)$/i;
document.addEventListener('turbo:before-prefetch', (e) => {
    const link = e.target;
    if (NO_PREFETCH.test(link.pathname) || link.hasAttribute('download')) e.preventDefault();
});

// 8. The admin panel scrolls inside <main data-turbo-scroll>, not the window, and Turbo only
//    keeps/restores *window* scroll — so every save (form posts back to the same page) and
//    every Back press landed at the top, which is what made it feel like a full reload.
//    Remembered per URL here: a same-page refresh keeps its position (unless the page came
//    back with errors to show — data-has-errors — which still start at the top, where they
//    are), and Back/Forward return to where that page was left.
const scrollMemory = new Map();
let scrollKey = location.href;
let visitAction = null;
const scrollBox = () => document.querySelector('[data-turbo-scroll]');
document.addEventListener('scroll', (e) => {
    if (e.target?.hasAttribute?.('data-turbo-scroll')) scrollMemory.set(scrollKey, e.target.scrollTop);
}, { capture: true, passive: true });
document.addEventListener('turbo:visit', (e) => { visitAction = e.detail.action; });
document.addEventListener('turbo:load', () => {
    const box = scrollBox();
    const previousKey = scrollKey;
    scrollKey = location.href;
    const action = visitAction;
    visitAction = null;
    if (!box || !action) return;
    let top = null;
    if (action === 'restore') top = scrollMemory.get(scrollKey);
    else if (action === 'replace' && previousKey === scrollKey && !box.hasAttribute('data-has-errors')) top = scrollMemory.get(previousKey);
    if (top) requestAnimationFrame(() => { box.scrollTop = top; });
});
