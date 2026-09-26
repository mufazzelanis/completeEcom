{{-- Admin alerts: header bell + unread badge, dropdown, in-page toasts, alert sound, and the
     "phone alerts" (Web Push) switch. Fed by GET admin/alerts/feed (polled), so it works on
     plain shared hosting with no websocket server. Sound is synthesized with the Web Audio
     API (no audio asset to host or cache). --}}
<style>
    @keyframes aa-ring {
        0%, 100% { transform: rotate(0); }
        10% { transform: rotate(16deg); } 20% { transform: rotate(-14deg); }
        30% { transform: rotate(11deg); } 40% { transform: rotate(-9deg); }
        50% { transform: rotate(6deg); }  60% { transform: rotate(-4deg); }
        70% { transform: rotate(2deg); }
    }
    .aa-ring { animation: aa-ring 1.1s ease-in-out; transform-origin: 50% 0; }
    @keyframes aa-toast-in {
        from { opacity: 0; transform: translateX(28px) scale(.97); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    .aa-toast { animation: aa-toast-in .28s cubic-bezier(.2,.9,.3,1.2); }
    @media (prefers-reduced-motion: reduce) { .aa-ring, .aa-toast { animation: none; } }
</style>

<script>
    window.__adminAlerts = {
        userId:         @json(auth()->id()),
        feedUrl:        @json(route('admin.alerts.feed')),
        readAllUrl:     @json(route('admin.alerts.read-all')),
        testUrl:        @json(route('admin.alerts.test')),
        subscribeUrl:   @json(route('admin.alerts.push.subscribe')),
        unsubscribeUrl: @json(route('admin.alerts.push.unsubscribe')),
        swUrl:          @json(route('admin.sw', [], false)),
        vapidKey:       @json((string) config('admin_alerts.vapid.public_key')),
        pollMs:         @json(max(5, (int) config('admin_alerts.poll_seconds', 12)) * 1000),
        csrf:           @json(csrf_token()),
    };

    window.adminAlerts = function () {
        const cfg = window.__adminAlerts;
        const lastIdKey = 'adminAlertsLastId:' + cfg.userId;

        const b64ToBytes = (b64) => {
            const pad = '='.repeat((4 - (b64.length % 4)) % 4);
            const raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
            return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
        };

        return {
            open: false,
            unread: 0,
            items: [],
            toasts: [],
            seq: 0,
            ringing: false,
            soundOn: true,
            soundBlocked: false,
            lastId: 0,
            baseTitle: document.title,
            polling: null,
            push: { state: 'checking', devices: 0, configured: true, busy: false, message: '' },

            async init() {
                this.soundOn = localStorage.getItem('adminAlertsSound') !== 'off';

                // Browsers refuse to play audio until the page has had a real user gesture.
                // The first click/tap/keypress anywhere unlocks it for the rest of the session.
                ['click', 'keydown', 'touchstart'].forEach((evt) =>
                    window.addEventListener(evt, () => this.unlockAudio(), { once: true, passive: true }));

                await this.poll();
                this.polling = setInterval(() => this.poll(), cfg.pollMs);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) this.poll(); });

                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.addEventListener('message', (event) => {
                        const msg = event.data || {};
                        if (msg.type === 'alert-arrived') this.poll();
                        if (msg.type === 'navigate' && msg.url) window.location.href = msg.url;
                    });
                }

                this.initPush();
            },

            // ── Feed polling ────────────────────────────────────────────────────────────
            async poll() {
                try {
                    const res = await fetch(cfg.feedUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin', cache: 'no-store',
                    });
                    // Logged out / session expired: stop hammering the server with requests
                    // that can only bounce to the login page.
                    if (res.redirected || res.status === 401 || res.status === 419) return clearInterval(this.polling);
                    if (!res.ok) return;
                    const data = await res.json();

                    this.items = data.items;
                    this.push.devices = data.push.devices;
                    this.push.configured = data.push.configured;
                    this.setUnread(data.unread);

                    // Another tab may already have announced these — share the watermark.
                    const stored = localStorage.getItem(lastIdKey);
                    this.lastId = Math.max(this.lastId, parseInt(stored || '0', 10));

                    if (stored === null) {
                        // First time this browser ever polls: don't replay the whole backlog.
                        localStorage.setItem(lastIdKey, String(data.latest_id));
                        this.lastId = data.latest_id;
                        return;
                    }

                    const fresh = data.items.filter((i) => i.id > this.lastId && !i.read).reverse();
                    if (data.latest_id > this.lastId) {
                        this.lastId = data.latest_id;
                        localStorage.setItem(lastIdKey, String(this.lastId));
                    }
                    if (fresh.length) this.announce(fresh);
                } catch (e) { /* offline / transient — the next tick retries */ }
            },

            announce(fresh) {
                const keys = fresh.slice(-3).map((item) => this.addToast(item));
                if (fresh.length > 3) {
                    this.addToast({ id: 'more', type: 'test', title: `${fresh.length - 3} more new alerts`, body: 'Open the bell to see them all.', open_url: '#' });
                }
                const kind = fresh.some((i) => i.type === 'order') ? 'order'
                    : fresh.some((i) => i.type === 'subscriber') ? 'subscriber' : 'test';
                // If the browser refuses to play (no user gesture yet on this page), say so right
                // on the toast — the panel that also explains it can only be opened with a click,
                // and that click itself unlocks audio, so the panel note alone would never be seen.
                this.playSound(kind).then(() => {
                    if (this.soundBlocked) this.toasts = this.toasts.map((t) => (keys.includes(t.key) ? { ...t, hint: true } : t));
                });
                this.ringing = false;
                this.$nextTick(() => { this.ringing = true; setTimeout(() => (this.ringing = false), 1300); });
            },

            setUnread(n) {
                this.unread = n;
                document.title = n > 0 ? `(${n}) ${this.baseTitle}` : this.baseTitle;
            },

            // ── Toasts ──────────────────────────────────────────────────────────────────
            addToast(item) {
                const key = ++this.seq;
                this.toasts.push({ ...item, key });
                setTimeout(() => this.dismissToast(key), item.type === 'order' ? 12000 : 8000);
                return key;
            },
            dismissToast(key) { this.toasts = this.toasts.filter((t) => t.key !== key); },

            // ── Panel actions ───────────────────────────────────────────────────────────
            toggle() { this.open = !this.open; if (this.open) this.poll(); },

            async markAllRead() {
                await fetch(cfg.readAllUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' }, credentials: 'same-origin' });
                this.items = this.items.map((i) => ({ ...i, read: true }));
                this.setUnread(0);
            },

            // ── Sound (Web Audio: two-harmonic bell tones, no audio file needed) ───────
            getCtx() {
                if (!window.__aaCtx) {
                    const AC = window.AudioContext || window.webkitAudioContext;
                    if (!AC) return null;
                    window.__aaCtx = new AC();
                }
                return window.__aaCtx;
            },
            unlockAudio() {
                const ctx = this.getCtx();
                if (ctx && ctx.state === 'suspended') ctx.resume().then(() => (this.soundBlocked = false)).catch(() => {});
            },
            toggleSound() {
                this.soundOn = !this.soundOn;
                localStorage.setItem('adminAlertsSound', this.soundOn ? 'on' : 'off');
                if (this.soundOn) { this.unlockAudio(); this.playSound('order', true); }
            },
            async playSound(kind, force = false) {
                if (!this.soundOn && !force) return;
                const ctx = this.getCtx();
                if (!ctx) return;
                if (ctx.state !== 'running') {
                    // resume() never rejects when there's been no user gesture — its promise
                    // just stays pending forever — so cap the wait instead of awaiting it.
                    try { await Promise.race([ctx.resume(), new Promise((r) => setTimeout(r, 300))]); } catch (e) {}
                }
                if (ctx.state !== 'running') { this.soundBlocked = true; return; }
                this.soundBlocked = false;

                // order: a bright rising four-note phrase played twice (hard to miss);
                // subscriber: a soft two-note "ding-dong"; anything else: a gentle ping.
                const phrase = kind === 'order'
                    ? [[783.99, 0], [987.77, .13], [1174.66, .26], [1567.98, .40]]
                    : kind === 'subscriber' ? [[880, 0], [1318.51, .17]] : [[880, 0], [1108.73, .14]];
                const repeats = kind === 'order' ? [0, 1.0] : [0];
                const t0 = ctx.currentTime + 0.03;

                repeats.forEach((shift) => phrase.forEach(([freq, offset], idx) => {
                    const start = t0 + shift + offset;
                    const last = idx === phrase.length - 1;
                    const tail = last ? 0.95 : 0.45;
                    [[freq, 'sine', 0.26], [freq * 2, 'triangle', 0.05]].forEach(([f, type, vol]) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = type;
                        osc.frequency.value = f;
                        gain.gain.setValueAtTime(0.0001, start);
                        gain.gain.exponentialRampToValueAtTime(vol, start + 0.012);
                        gain.gain.exponentialRampToValueAtTime(0.0001, start + tail);
                        osc.connect(gain).connect(ctx.destination);
                        osc.start(start);
                        osc.stop(start + tail + 0.05);
                    });
                }));
            },

            // ── Phone alerts (Web Push) ─────────────────────────────────────────────────
            async initPush() {
                const p = this.push;
                const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
                const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;

                if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                    p.state = ios && !standalone ? 'ios-install' : 'unsupported';
                    return;
                }
                if (Notification.permission === 'denied') { p.state = 'denied'; return; }

                p.state = 'off';
                if (Notification.permission !== 'granted') return;

                try {
                    const reg = await navigator.serviceWorker.register(cfg.swUrl, { scope: '/admin/' });
                    await this.whenActive(reg);
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        p.state = 'on';
                        // Re-assert once per browser session so a subscription always maps
                        // to whoever is signed in right now (shared phone, second admin).
                        if (!sessionStorage.getItem('adminAlertsSubSynced')) {
                            await this.syncSubscription(sub);
                            sessionStorage.setItem('adminAlertsSubSynced', '1');
                        }
                    }
                } catch (e) { /* leave state 'off'; the user can retry from the panel */ }
            },

            whenActive(reg) {
                return new Promise((resolve) => {
                    const worker = reg.installing || reg.waiting || reg.active;
                    if (!worker || worker.state === 'activated') return resolve();
                    worker.addEventListener('statechange', () => { if (worker.state === 'activated') resolve(); });
                });
            },

            // A redirected response means the session expired (the request bounced to the login
            // page, which fetch follows into a "200 OK" HTML page) — never treat that as success.
            async ensureOk(res, fallback) {
                if (res.redirected) throw new Error('Your session expired — reload the page and sign in again.');
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    throw new Error((body && body.message) || fallback + ' (' + res.status + ').');
                }
            },

            async syncSubscription(sub) {
                const res = await fetch(cfg.subscribeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    credentials: 'same-origin',
                    body: JSON.stringify(sub.toJSON()),
                });
                await this.ensureOk(res, 'The server rejected this device');
            },

            async enablePush() {
                const p = this.push;
                p.busy = true; p.message = '';
                try {
                    if (!cfg.vapidKey) throw new Error('Phone alerts are not set up on the server yet.');
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') { p.state = permission === 'denied' ? 'denied' : 'off'; return; }

                    const reg = await navigator.serviceWorker.register(cfg.swUrl, { scope: '/admin/' });
                    await this.whenActive(reg);
                    const sub = (await reg.pushManager.getSubscription())
                        || await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToBytes(cfg.vapidKey) });
                    await this.syncSubscription(sub);

                    p.state = 'on';
                    p.message = 'Phone alerts are on for this device.';
                    this.poll();
                } catch (e) {
                    p.message = e && e.message ? e.message : 'Could not turn on phone alerts.';
                } finally { p.busy = false; }
            },

            async disablePush() {
                const p = this.push;
                p.busy = true; p.message = '';
                try {
                    const reg = await navigator.serviceWorker.getRegistration('/admin/');
                    const sub = reg ? await reg.pushManager.getSubscription() : null;
                    if (sub) {
                        const res = await fetch(cfg.unsubscribeUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                            credentials: 'same-origin',
                            body: JSON.stringify({ endpoint: sub.endpoint }),
                        });
                        // Only drop the browser-side subscription once the server has forgotten
                        // it, otherwise the two disagree and alerts keep going to a "turned off" phone.
                        await this.ensureOk(res, 'Could not turn off phone alerts');
                        await sub.unsubscribe();
                    }
                    p.state = 'off';
                    p.message = 'Phone alerts are off for this device.';
                    this.poll();
                } catch (e) {
                    p.message = e && e.message ? e.message : 'Could not turn off phone alerts.';
                } finally { p.busy = false; }
            },

            async sendTest() {
                const p = this.push;
                p.busy = true; p.message = '';
                try {
                    this.unlockAudio();
                    const res = await fetch(cfg.testUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' }, credentials: 'same-origin' });
                    const data = await res.json();
                    p.message = !data.configured ? 'Test alert created. Phone push is not set up on the server yet.'
                        : data.devices === 0 ? 'Test alert created. Turn on phone alerts to receive it on a phone too.'
                        : 'Test alert sent to ' + data.devices + ' device' + (data.devices === 1 ? '' : 's') + '.';
                    setTimeout(() => this.poll(), 500);
                } catch (e) {
                    p.message = 'Could not send the test alert.';
                } finally { p.busy = false; }
            },
        };
    };
</script>

<div x-data="adminAlerts()" x-init="init()" @keydown.escape.window="open = false" class="relative">
    <button type="button" @click="toggle()"
            class="relative p-2 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
            :aria-label="unread ? 'Alerts, ' + unread + ' unread' : 'Alerts'" aria-haspopup="true" :aria-expanded="open">
        <svg class="w-5 h-5" :class="ringing ? 'aa-ring' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span x-show="unread > 0" x-cloak
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white dark:ring-gray-900"
              x-text="unread > 99 ? '99+' : unread"></span>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.outside="open = false"
         class="fixed sm:absolute inset-x-2 sm:inset-x-auto top-16 sm:top-full sm:right-0 sm:mt-2 sm:w-[24rem] max-h-[78vh] flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Alerts</h3>
                <span x-show="unread > 0" x-cloak class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-300" x-text="unread + ' new'"></span>
            </div>
            <button type="button" @click="markAllRead()" x-show="unread > 0" x-cloak class="text-xs font-medium text-orange-600 hover:text-orange-700">Mark all read</button>
        </div>

        <div class="overflow-y-auto flex-1 divide-y divide-gray-50 dark:divide-gray-800">
            <template x-for="item in items" :key="item.id">
                <a :href="item.open_url" class="flex gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                   :class="item.read ? '' : 'bg-orange-50/70 dark:bg-orange-900/10'">
                    <span class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center"
                          :class="item.type === 'order' ? 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-300' : item.type === 'subscriber' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300'">
                        <svg x-show="item.type === 'order'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <svg x-show="item.type === 'subscriber'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <svg x-show="item.type !== 'order' && item.type !== 'subscriber'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="item.title"></span>
                            <span class="text-[11px] text-gray-400 flex-shrink-0 mt-0.5" x-text="item.time_ago"></span>
                        </span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" x-text="item.body"></span>
                    </span>
                    <span x-show="!item.read" class="w-2 h-2 rounded-full bg-orange-500 flex-shrink-0 mt-2"></span>
                </a>
            </template>
            <div x-show="items.length === 0" class="px-4 py-10 text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No alerts yet</p>
                <p class="text-xs text-gray-400 mt-1">New orders and newsletter signups will show up here.</p>
            </div>
        </div>

        {{-- Sound + phone controls --}}
        <div class="border-t border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 px-4 py-3 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Alert sound</p>
                    <p class="text-[11px] text-gray-400" x-show="!soundBlocked">Plays when something new arrives while this page is open.</p>
                    <p class="text-[11px] text-amber-600" x-show="soundBlocked" x-cloak>Browser blocked sound — click anywhere on the page once to allow it.</p>
                </div>
                <button type="button" @click="toggleSound()" role="switch" :aria-checked="soundOn"
                        class="relative w-10 h-6 rounded-full transition flex-shrink-0" :class="soundOn ? 'bg-orange-500' : 'bg-gray-300 dark:bg-gray-600'">
                    <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="soundOn ? 'translate-x-4' : ''"></span>
                </button>
            </div>

            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Phone alerts</p>
                    <p class="text-[11px] text-gray-400" x-show="push.state === 'on'" x-cloak>On for this device<span x-show="push.devices > 1" x-text="' · ' + push.devices + ' devices total'"></span>.</p>
                    <p class="text-[11px] text-gray-400" x-show="push.state === 'off' && push.configured" x-cloak>Get a notification on your phone even when the app is closed.</p>
                    <p class="text-[11px] text-amber-600" x-show="!push.configured" x-cloak>Not set up on the server yet (run <code>php artisan admin-alerts:setup</code>).</p>
                    <p class="text-[11px] text-amber-600" x-show="push.state === 'denied'" x-cloak>Blocked in this browser — allow notifications for this site in its settings.</p>
                    <p class="text-[11px] text-amber-600" x-show="push.state === 'ios-install'" x-cloak>On iPhone: tap Share → "Add to Home Screen", then open the app from your home screen.</p>
                    <p class="text-[11px] text-gray-400" x-show="push.state === 'unsupported'" x-cloak>This browser doesn't support phone notifications.</p>
                </div>
                <button type="button" @click="push.state === 'on' ? disablePush() : enablePush()" :disabled="push.busy"
                        x-show="push.configured && (push.state === 'off' || push.state === 'on')" x-cloak
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg flex-shrink-0 transition disabled:opacity-50"
                        :class="push.state === 'on' ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200' : 'bg-orange-600 text-white hover:bg-orange-700'"
                        x-text="push.state === 'on' ? 'Turn off' : 'Turn on'"></button>
            </div>

            <p x-show="push.message" x-cloak class="text-[11px] text-gray-500 dark:text-gray-400" x-text="push.message"></p>

            <div class="flex items-center justify-between pt-1">
                <button type="button" @click="sendTest()" :disabled="push.busy" class="text-xs font-medium text-orange-600 hover:text-orange-700 disabled:opacity-50">Send me a test alert</button>
                <a href="{{ route('admin.alerts.index') }}" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">View all &rarr;</a>
            </div>
        </div>
    </div>

    {{-- Toasts: shown for anything that arrives while this page is open --}}
    {{-- Top on phones (the bottom is the app's tab bar), bottom-right on desktop so a toast
         never sits on top of the bell panel that drops down from the header. --}}
    <div class="fixed top-20 sm:top-auto right-3 sm:right-6 sm:bottom-6 z-[60] flex flex-col gap-2 w-[calc(100vw-1.5rem)] sm:w-80 pointer-events-none">
        <template x-for="t in toasts" :key="t.key">
            <a :href="t.open_url" class="aa-toast pointer-events-auto flex gap-3 items-start p-3 rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 hover:shadow-2xl transition">
                <span class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center"
                      :class="t.type === 'order' ? 'bg-green-100 text-green-600' : t.type === 'subscriber' ? 'bg-blue-100 text-blue-600' : 'bg-orange-100 text-orange-600'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-gray-900 dark:text-gray-100 truncate" x-text="t.title"></span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2" x-text="t.body"></span>
                    <span x-show="t.hint" x-cloak class="block text-[10px] font-medium text-amber-600 mt-1">Sound is blocked by your browser — click anywhere on this page once to turn it on.</span>
                </span>
                <button type="button" @click.prevent.stop="dismissToast(t.key)" class="text-gray-300 hover:text-gray-500 flex-shrink-0" aria-label="Dismiss">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </a>
        </template>
    </div>
</div>
