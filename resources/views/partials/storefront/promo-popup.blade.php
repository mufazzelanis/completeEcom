{{-- Promotion popup (Marketing → Promo Popups). The server keeps only the popups that fit this page
     (PromoPopup::forPage); the script below applies the visitor rules — device, first-time/returning,
     traffic source, trigger and how often — and shows at most one. Bottom sheet on phones, centred
     card on desktop. Never on cart / checkout / account pages, never for bots.
     Admins can preview one anywhere with ?promo_preview={id} (shown at once, no rules, not counted). --}}
@php
    $ppRoute = request()->route();
    $ppName = (string) $ppRoute?->getName();
    $ppPopups = [];
    $ppPreview = false;
    $ppBlocked = preg_match('/^(cart|checkout|account|orders|login|register|password|verification|two-factor|logout|landing)/', $ppName)
        || \App\Models\ActivityLog::isBotUserAgent((string) request()->userAgent());

    if (request()->filled('promo_preview') && auth()->user()?->isAdmin()) {
        $pp = \App\Models\PromoPopup::find((int) request('promo_preview'));
        if ($pp) {
            $ppPopups = [array_merge($pp->toClient(), ['device' => 'all', 'audience' => 'all', 'source' => 'all', 'trigger' => 'delay', 'triggerValue' => 0])];
            $ppPreview = true;
        }
    } elseif (!$ppBlocked) {
        $ppCtx = ['page' => 'other'];
        if ($ppName === 'home') {
            $ppCtx['page'] = 'home';
        } elseif ($ppName === 'products.show' && ($prod = $ppRoute->parameter('product')) instanceof \App\Models\Product) {
            $ppCtx = ['page' => 'product', 'product' => $prod->id, 'categories' => array_values(array_filter([$prod->category_id, $prod->subcategory_id]))];
        } elseif ($ppName === 'shop.category' && ($cat = $ppRoute->parameter('category')) instanceof \App\Models\Category) {
            $ppCtx = ['page' => 'category', 'categories' => array_values(array_filter([$cat->id, $cat->parent_id]))];
        } elseif (in_array($ppName, ['shop.index', 'shop.brand', 'shop.brands', 'shop.categories', 'brands', 'categories'], true)) {
            $ppCtx['page'] = 'shop';
        } elseif (str_starts_with($ppName, 'blog.')) {
            $ppCtx['page'] = 'blog';
        }
        $ppPopups = \App\Models\PromoPopup::forPage($ppCtx);
    }
@endphp
@if($ppPopups)
<style>
    .pp-root[x-cloak] { display: none; }
    .pp-wrap { position: fixed; inset: 0; z-index: 9990; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .pp-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(2px); }
    .pp-sheet { position: relative; width: 100%; max-width: 440px; display: flex; justify-content: center; max-height: calc(100vh - 2rem); overflow-y: auto; border-radius: 1.25rem; overscroll-behavior: contain; }
    .pp-in { transition: opacity .35s cubic-bezier(.16,1,.3,1), transform .45s cubic-bezier(.16,1,.3,1); }
    .pp-out { transition: opacity .2s ease-in, transform .25s ease-in; }
    .pp-from { opacity: 0; transform: translateY(18px) scale(.94); }
    .pp-to { opacity: 1; transform: none; }
    @media (max-width: 767px) {
        .pp-wrap { align-items: flex-end; padding: 0; }
        .pp-sheet { max-width: none; max-height: 88vh; border-radius: 1.4rem 1.4rem 0 0; }
        .pp-from { opacity: 1; transform: translateY(105%); }
    }
    @media (prefers-reduced-motion: reduce) { .pp-in, .pp-out { transition: opacity .2s; } .pp-from { transform: none; } }
</style>
<script>
    window.promoPopup = window.promoPopup || function (cfg) {
        const S = (store, k, v) => { try { return v === undefined ? store.getItem(k) : store.setItem(k, v); } catch (e) { return null; } };
        const L = (k, v) => S(localStorage, 'mvp:' + k, v), SS = (k, v) => S(sessionStorage, 'mvp:' + k, v);
        const DAY = 864e5;

        // once per browser visit: first-time vs returning, and where this visit came from
        if (!SS('s')) {
            SS('s', '1');
            SS('ret', L('v') ? '1' : '0');
            const q = new URLSearchParams(location.search), ref = document.referrer.toLowerCase(), utm = (q.get('utm_source') || '').toLowerCase();
            const src = q.has('fbclid') || /facebook|instagram|^fb|^ig/.test(utm) || /facebook\.|instagram\.|fb\.me|m\.me/.test(ref) ? 'facebook'
                : q.has('gclid') || /google/.test(utm) || /google\./.test(ref) ? 'google'
                : (!ref || ref.includes(location.hostname)) && !utm ? 'direct' : 'other';
            SS('src', src);
        }
        L('v', String(Date.now()));
        if (cfg.page === 'product') SS('pv', String(+(SS('pv') || 0) + 1));

        return {
            open: false, p: cfg.popups[0], cd: '', copied: false, timers: [], offs: [], dragY: 0, img: null, waiting: false,
            init() {
                if (cfg.preview) { this.p = cfg.popups[0]; return this.arm(); }
                const mobile = matchMedia('(max-width: 767px)').matches;
                this.p = cfg.popups.find((p) =>
                    (p.device === 'all' || (p.device === 'mobile') === mobile)
                    && (p.audience === 'all' || (p.audience === 'returning') === (SS('ret') === '1'))
                    && (p.source === 'all' || p.source === SS('src'))
                    && this.due(p));
                if (this.p) this.arm();
            },
            // frequency: session / daily / every N days / once ever
            due(p) {
                if (p.frequency === 'session') return !SS('p' + p.id);
                const last = +(L('p' + p.id) || 0);
                if (!last) return true;
                if (p.frequency === 'once') return false;
                return Date.now() - last > (p.frequency === 'daily' ? 1 : p.frequencyDays) * DAY;
            },
            arm() {
                const p = this.p, v = p.triggerValue;
                if (p.image) this.img = this.load(p.image); // fetched while waiting for the trigger
                const on = (el, ev, fn, o) => { el.addEventListener(ev, fn, o); this.offs.push(() => el.removeEventListener(ev, fn, o)); };
                const later = (ms) => this.timers.push(setTimeout(() => this.show(), ms));
                const mobile = matchMedia('(max-width: 767px)').matches;
                if (p.trigger === 'scroll') {
                    const check = () => { const h = document.documentElement.scrollHeight - innerHeight; if (h <= 0 || scrollY / h * 100 >= v) this.show(); };
                    on(window, 'scroll', check, { passive: true }); later(60000); // short pages: still shown after a minute
                } else if (p.trigger === 'exit' && !mobile) {
                    on(document, 'mouseout', (e) => { if (!e.relatedTarget && e.clientY <= 0) this.show(); });
                } else if (p.trigger === 'exit') {
                    later(15000); // phones have no "leaving" gesture to watch
                } else if (p.trigger === 'views') {
                    if (+(SS('pv') || 0) >= Math.max(1, v)) later(1500);
                } else {
                    later(Math.max(0, v) * 1000 || 400);
                }
            },
            load(src) {
                return new Promise((res) => {
                    const i = new Image(), t = setTimeout(() => res(false), 8000);
                    i.onload = () => { clearTimeout(t); res(true); };
                    i.onerror = () => { clearTimeout(t); res(false); };
                    i.src = src;
                });
            },
            async show() {
                if (this.open || this.waiting || !this.p || document.hidden && !cfg.preview) return;
                // don't cover an open menu / drawer / another dialog
                if (document.body.style.overflow === 'hidden' && !cfg.preview) { this.timers.push(setTimeout(() => this.show(), 4000)); return; }
                this.stop();
                // open only once the picture is in — never an empty card that jumps when it arrives;
                // a picture that won't load drops a picture-only popup, and turns picture+text into text
                if (this.p.image) {
                    this.waiting = true;
                    const ok = await (this.img || this.load(this.p.image));
                    this.waiting = false;
                    if (!ok) {
                        if (this.p.layout === 'image') return;
                        this.p = { ...this.p, image: null, layout: 'text' };
                    }
                }
                this.open = true;
                if (this.p.countdown) this.tick();
                if (!cfg.preview) {
                    this.p.frequency === 'session' ? SS('p' + this.p.id, '1') : L('p' + this.p.id, String(Date.now()));
                    this.track('view');
                }
                this.$nextTick(() => this.$root.querySelector('.pp-x')?.focus({ preventScroll: true }));
            },
            tick() {
                const run = () => {
                    const s = Math.floor((this.p.countdown - Date.now()) / 1000);
                    if (s <= 0) { this.cd = ''; return; }
                    const d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), sec = s % 60, pad = (n) => String(n).padStart(2, '0');
                    this.cd = (d ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(sec);
                };
                run(); this.timers.push(setInterval(run, 1000));
            },
            close() {
                if (!this.open) return;
                this.open = false;
                if (!cfg.preview) this.track('close');
            },
            go() {
                const url = this.p.url;
                if (!url) return;
                if (!cfg.preview) {
                    this.track('click');
                    window.mvTrack && mvTrack('select_promotion', { promotion_id: 'popup-' + this.p.id, promotion_name: this.p.name, creative_slot: 'popup' });
                    window.fbq && fbq('trackCustom', 'PromoPopupClick', { popup: this.p.name });
                }
                this.open = false;
                if (this.p.newTab) return window.open(url, '_blank', 'noopener');
                const u = new URL(url, location.href);
                u.origin === location.origin && window.Turbo ? Turbo.visit(u.href) : (location.href = u.href);
            },
            async copy() {
                try { await navigator.clipboard.writeText(this.p.coupon); }
                catch (e) { const t = document.createElement('textarea'); t.value = this.p.coupon; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); }
                this.copied = true; this.timers.push(setTimeout(() => (this.copied = false), 2200));
            },
            track(e) {
                fetch(cfg.track.replace('__ID__', this.p.id), { method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }, body: JSON.stringify({ e }) }).catch(() => {});
            },
            // phones: drag the sheet down to dismiss
            dragStart(e) { if (this.$refs.sheet.scrollTop > 0) return; this.dragY0 = e.touches[0].clientY; this.dragY = 0; },
            dragMove(e) { if (this.dragY0 == null) return; this.dragY = Math.max(0, e.touches[0].clientY - this.dragY0); },
            dragEnd() { if (this.dragY0 == null) return; this.dragY0 = null; if (this.dragY > 90) this.close(); this.dragY = 0; },
            stop() { this.offs.forEach((f) => f()); this.offs = []; },
            destroy() { this.stop(); this.timers.forEach((t) => { clearTimeout(t); clearInterval(t); }); },
        };
    };
</script>
<div class="pp-root" x-cloak
     x-data="promoPopup(@js(['popups' => $ppPopups, 'preview' => $ppPreview, 'page' => $ppCtx['page'] ?? 'other', 'track' => route('promo-popups.track', '__ID__')]))"
     @keydown.escape.window="close()">
    <div class="pp-wrap" x-show="open" role="dialog" aria-modal="true" :aria-labelledby="p && p.title ? 'pp-t-' + p.id : null" :aria-label="p && !p.title ? 'Special offer' : null">
        <div class="pp-backdrop" @click="close()" x-show="open" x-transition.opacity.duration.300ms></div>
        <div class="pp-sheet" x-ref="sheet" x-show="open"
             x-transition:enter="pp-in" x-transition:enter-start="pp-from" x-transition:enter-end="pp-to"
             x-transition:leave="pp-out" x-transition:leave-start="pp-to" x-transition:leave-end="pp-from"
             :style="dragY ? 'transform: translateY(' + dragY + 'px); transition: none' : ''"
             @touchstart.passive="dragStart($event)" @touchmove.passive="dragMove($event)" @touchend="dragEnd()">
            <template x-if="p">
                <div style="width: 100%; display: flex; justify-content: center;">
                    @include('partials.promo-popup-card')
                </div>
            </template>
        </div>
    </div>
</div>
@endif
