{{-- Order quick view — shared by the Orders list and the Dashboard.
     @include('admin.orders._quickview', ['part' => 'assets'])  once, outside the component;
     @include('admin.orders._quickview', ['part' => 'drawer'])  inside an x-data built with
     withOrderDrawer(cfg, {...}). Open one with show(id) or $dispatch('oq-open', id). --}}
@if(($part ?? 'assets') === 'assets')
<style>
    /* quick view drawer */
    .ob-ov { position: fixed; inset: 0; z-index: 80; background: rgba(17,24,39,.45); display: flex; justify-content: flex-end; }
    .ob-panel { width: 34rem; max-width: 100%; height: 100%; background: #f8fafc; display: flex; flex-direction: column; box-shadow: -12px 0 40px rgba(0,0,0,.18); }
    .ob-bar { display: flex; align-items: center; gap: .4rem; padding: .7rem .9rem; background: #fff; border-bottom: 1px solid #f1f5f9; }
    .ob-bar button { width: 2.25rem; height: 2.25rem; border-radius: .7rem; display: inline-flex; align-items: center; justify-content: center; color: #4b5563; }
    .ob-bar button:hover:not(:disabled) { background: #f3f4f6; }
    .ob-bar button:disabled { opacity: .3; }
    .ob-body { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: .9rem; }
    .ob-skel { height: 6rem; border-radius: 1rem; background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 50%, #eef2f7 75%); background-size: 200% 100%; animation: obSk 1.2s infinite; margin-bottom: .75rem; }
    @keyframes obSk { to { background-position: -200% 0; } }

    .oq { display: flex; flex-direction: column; gap: .75rem; }
    .oq-head { padding: .1rem .2rem; }
    .oq-num { font-size: 1.15rem; font-weight: 800; color: #111827; }
    .oq-sub, .oq-line { font-size: .78rem; color: #6b7280; margin-top: .15rem; }
    .oq-tag { display: inline-flex; align-items: center; font-size: .66rem; font-weight: 700; padding: .12rem .5rem; border-radius: 999px; letter-spacing: .02em; vertical-align: middle; }
    .oq-card { background: #fff; border-radius: 1rem; padding: .9rem 1rem; border: 1px solid #eef2f7; }
    .oq-steps { display: grid; grid-template-columns: repeat(4, 1fr); position: relative; }
    .oq-step { display: flex; flex-direction: column; align-items: center; gap: .3rem; position: relative; padding: .1rem 0; }
    .oq-step::before { content: ''; position: absolute; top: .85rem; left: -50%; width: 100%; height: 3px; background: #e5e7eb; z-index: 0; }
    .oq-step:first-child::before { display: none; }
    .oq-step.is-done::before { background: #10b981; }
    .oq-dot { position: relative; z-index: 1; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800; background: #fff; border: 2px solid #d1d5db; color: #9ca3af; transition: all .15s; }
    .oq-step.is-done .oq-dot { background: #10b981; border-color: #10b981; color: #fff; }
    .oq-step.is-now .oq-dot { box-shadow: 0 0 0 4px rgba(16,185,129,.2); }
    .oq-step:hover:not(:disabled) .oq-dot { border-color: #10b981; }
    .oq-step-l { font-size: .72rem; font-weight: 600; color: #6b7280; }
    .oq-step.is-now .oq-step-l { color: #047857; font-weight: 800; }
    .oq-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .5rem .85rem; border-radius: .75rem; font-size: .82rem; font-weight: 700; background: #fff; border: 1px solid #e5e7eb; color: #374151; }
    .oq-btn:hover:not(:disabled) { border-color: #9ca3af; }
    .oq-btn:disabled { opacity: .5; }
    .oq-primary { background: #059669; border-color: #059669; color: #fff; }
    .oq-primary:hover:not(:disabled) { background: #047857; border-color: #047857; }
    .oq-avatar { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: linear-gradient(135deg, #f97316, #db2777); color: #fff; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .oq-name { font-weight: 800; color: #111827; display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .oq-act { display: flex; align-items: center; justify-content: center; gap: .3rem; white-space: nowrap; letter-spacing: -.01em; padding: .55rem .2rem; border-radius: .75rem; background: #f8fafc; border: 1px solid #eef2f7; font-size: .8rem; font-weight: 700; color: #374151; }
    .oq-act:hover { background: #f1f5f9; }
    .oq-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem; margin-top: .75rem; }
    .oq-stats div { text-align: center; background: #f8fafc; border-radius: .7rem; padding: .45rem .2rem; }
    .oq-stats b { display: block; font-size: 1.05rem; font-weight: 800; color: #111827; }
    .oq-stats span { font-size: .66rem; color: #6b7280; font-weight: 600; text-transform: uppercase; }
    .oq-courier { margin-top: .75rem; padding-top: .7rem; border-top: 1px dashed #e5e7eb; }
    .oq-bar { height: .45rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-top: .35rem; }
    .oq-bar span { display: block; height: 100%; border-radius: 999px; }
    .oq-mini { font-size: .75rem; font-weight: 700; color: #4f46e5; padding: .25rem .55rem; border-radius: .55rem; background: #eef2ff; }
    .oq-mini:hover { background: #e0e7ff; }
    .oq-note-c { margin-top: .6rem; padding: .55rem .7rem; border-radius: .7rem; background: #fffbeb; color: #92400e; font-size: .82rem; }
    .oq-thumb { width: 2.75rem; height: 2.75rem; border-radius: .7rem; background: #f8fafc; border: 1px solid #eef2f7; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .oq-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .oq-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .oq-sum { margin-top: .8rem; padding-top: .7rem; border-top: 1px solid #f1f5f9; font-size: .85rem; }
    .oq-sum div { display: flex; justify-content: space-between; gap: .5rem; padding: .15rem 0; color: #4b5563; }
    .oq-sum dd { font-weight: 600; color: #111827; }
    .oq-sum .oq-total { margin-top: .3rem; padding-top: .4rem; border-top: 1px dashed #e5e7eb; font-size: 1rem; }
    .oq-sum .oq-total dt, .oq-sum .oq-total dd { font-weight: 800; color: #111827; }
    .oq-collect { margin-top: .6rem; display: flex; justify-content: space-between; align-items: center; padding: .6rem .8rem; border-radius: .8rem; background: #fff7ed; color: #9a3412; font-size: .85rem; font-weight: 700; }
    .oq-collect b { font-size: 1.1rem; }
    .oq-seg { display: grid; grid-template-columns: repeat(4, 1fr); gap: .3rem; background: #f1f5f9; padding: .25rem; border-radius: .8rem; }
    .oq-seg button { padding: .45rem .2rem; border-radius: .6rem; font-size: .78rem; font-weight: 700; color: #6b7280; }
    .oq-seg button.is-on { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    .oq-seg .is-paid { color: #059669; } .oq-seg .is-pending { color: #b45309; } .oq-seg .is-failed { color: #dc2626; } .oq-seg .is-refunded { color: #374151; }
    .oq-textarea { width: 100%; margin-top: .4rem; border: 1px solid #e5e7eb; border-radius: .75rem; padding: .55rem .7rem; font-size: .875rem; resize: vertical; background: #fff; color: #111827; }
    .oq-textarea:focus { outline: none; border-color: #fb923c; box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
    .oq-hist { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .5rem 0; font-size: .82rem; text-align: left; }
    .oq-hist:hover { background: #fafafa; }
    .oq-st { font-size: .66rem; font-weight: 700; padding: .1rem .45rem; border-radius: 999px; }
    .oq-st-pending { background: #fef3c7; color: #b45309; } .oq-st-processing { background: #dbeafe; color: #1d4ed8; } .oq-st-shipped { background: #ede9fe; color: #6d28d9; }
    .oq-st-delivered { background: #d1fae5; color: #047857; } .oq-st-cancelled { background: #fee2e2; color: #b91c1c; } .oq-st-refunded { background: #f3f4f6; color: #374151; }
    .oq-tl { list-style: none; margin: 0; padding: 0 0 0 1rem; border-left: 2px solid #f1f5f9; }
    .oq-tl li { position: relative; padding: 0 0 .7rem .4rem; }
    .oq-tl-dot { position: absolute; left: -1.38rem; top: .3rem; width: .7rem; height: .7rem; border-radius: 999px; background: #94a3b8; border: 2px solid #fff; }
    .oq-foot { display: flex; flex-wrap: wrap; gap: .5rem; padding-bottom: .5rem; }
    .dark .oq-card, .dark .ob-bar, .dark .oq-btn { background: #111827; border-color: #1f2937; }
    .dark .oq-num, .dark .oq-name, .dark .oq-stats b, .dark .oq-sum dd, .dark .oq-sum .oq-total dt, .dark .oq-sum .oq-total dd { color: #f9fafb; }
    .dark .oq-btn { color: #d1d5db; }
    .dark .oq-textarea { background: #1f2937; border-color: #374151; color: #f3f4f6; }
    .dark .ob-panel { background: #030712; }
    .dark .oq-stats div, .dark .oq-act, .dark .oq-thumb, .dark .oq-seg { background: #1f2937; border-color: #374151; color: #e5e7eb; }
    .dark .oq-seg button.is-on { background: #374151; }
    .dark .oq-dot { background: #111827; }
    .ob-loading { opacity: .5; pointer-events: none; }
    @media (max-width: 767px) {
        .ob-ov { align-items: flex-end; }
        .ob-panel { width: 100%; height: 94vh; border-radius: 1.25rem 1.25rem 0 0; }
        .ob-hide-m { display: none !important; }
    }
</style>
<script>
    // Drawer state: which order is open, prev/next through `ids`, deep link #order-ID.
    window.orderDrawer = function (cfg) {
        return {
            ids: cfg.ids || [], open: false, loading: false, current: null, html: '',
            init() {
                const m = location.hash.match(/^#order-(\d+)$/);
                if (m) this.show(+m[1]);
            },
            async show(id) {
                this.open = true; this.current = id; this.loading = true;
                history.replaceState(history.state, '', location.pathname + location.search + '#order-' + id);
                try {
                    const r = await fetch(cfg.quickUrl.replace('__ID__', id), { headers: { Accept: 'application/json' } });
                    if (!r.ok) throw new Error();
                    const d = await r.json();
                    if (this.current !== id) return;   // a newer one was asked for meanwhile
                    this.html = d.html;
                    this.$nextTick(() => this.$refs.body && (this.$refs.body.scrollTop = 0));
                } catch (e) {
                    this.html = '<p style="padding:2rem;text-align:center;color:#dc2626">Could not load this order — please try again.</p>';
                } finally { this.loading = false; }
            },
            close() {
                if (!this.open) return;
                this.open = false; this.html = ''; this.current = null;
                history.replaceState(history.state, '', location.pathname + location.search);
            },
            get pos() { return this.ids.indexOf(this.current); },
            step(d) { const i = this.pos + d; if (i >= 0 && i < this.ids.length) this.show(this.ids[i]); },
            key(e) {
                if (!this.open || e.target.closest('input, textarea, select, [contenteditable]')) return;
                if (e.key === 'ArrowRight' || e.key === 'j') { e.preventDefault(); this.step(1); }
                if (e.key === 'ArrowLeft' || e.key === 'k') { e.preventDefault(); this.step(-1); }
            },
        };
    };
    // drawer + page-specific members (property descriptors, so getters stay getters)
    window.withOrderDrawer = function (cfg, extra) {
        const o = window.orderDrawer(cfg);
        Object.defineProperties(o, Object.getOwnPropertyDescriptors(extra));
        return o;
    };
    if (!window.ordersListToast) {
        window.ordersListToast = function (success, message) {
            document.getElementById('orders-list-toast')?.remove();
            const t = document.createElement('div');
            t.id = 'orders-list-toast';
            t.style.cssText = 'position:fixed;top:5rem;right:1.25rem;z-index:100;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:600;color:#fff;box-shadow:0 10px 25px rgba(0,0,0,.15);background:' + (success ? '#16a34a' : '#dc2626');
            t.textContent = message;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        };
    }

    // One order inside the drawer (admin/orders/_quick.blade.php)
    window.orderQuick = function (cfg) {
        const send = async (url, method, body) => {
            const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: body ? JSON.stringify(body) : undefined });
            const d = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(d.message || 'Could not save — please try again.');
            return d;
        };
        const NEXT = { pending: { key: 'processing', label: '✓ Confirm order' }, processing: { key: 'shipped', label: '🚚 Mark as shipped' }, shipped: { key: 'delivered', label: '🎉 Mark as delivered' } };
        return {
            cfg, status: cfg.status, payment: cfg.payment_status, note: cfg.note, noteState: '',
            busy: false, courier: cfg.courier, courierBusy: false,
            get closed() { return ['cancelled', 'refunded'].includes(this.status); },
            get stepIndex() { return ['pending', 'processing', 'shipped', 'delivered'].indexOf(this.status); },
            get nextStep() { return this.closed ? null : (NEXT[this.status] || null); },
            broadcast() { window.dispatchEvent(new CustomEvent('order-updated', { detail: { id: cfg.id, status: this.status, payment_status: this.payment } })); },
            async setStatus(s) {
                if (s === this.status || this.busy) return;
                if (['cancelled', 'refunded'].includes(s) && !(await showConfirmModal(s === 'cancelled' ? 'Cancel this order? Its stock is put back.' : 'Mark this order as refunded?'))) return;
                this.busy = true;
                try {
                    const d = await send(cfg.urls.status, 'PATCH', { status: s });
                    this.status = d.status; if (d.payment_status) this.payment = d.payment_status;
                    this.broadcast(); ordersListToast(true, 'Status → ' + (s === 'processing' ? 'Confirmed' : s.charAt(0).toUpperCase() + s.slice(1)));
                } catch (e) { ordersListToast(false, e.message); } finally { this.busy = false; }
            },
            async setPayment(p) {
                if (p === this.payment || this.busy) return;
                this.busy = true;
                try {
                    const d = await send(cfg.urls.payment, 'PUT', { payment_status: p });
                    this.payment = d.payment_status; this.broadcast(); ordersListToast(true, 'Payment → ' + p);
                } catch (e) { ordersListToast(false, e.message); } finally { this.busy = false; }
            },
            async saveNote() {
                this.noteState = 'Saving…';
                try { await send(cfg.urls.note, 'PATCH', { admin_note: this.note }); this.noteState = 'Saved ✓'; }
                catch (e) { this.noteState = 'Not saved — retry'; }
            },
            async checkCourier() {
                this.courierBusy = true;
                try { this.courier = await send(cfg.urls.courier, 'POST'); }
                catch (e) { this.courier = { success: false, error: e.message }; }
                finally { this.courierBusy = false; }
            },
            copy(text, msg) {
                const done = () => ordersListToast(true, msg);
                if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text).then(done);
                const t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
                document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done();
            },
        };
    };
</script>
@else
{{-- Quick view drawer --}}
<div class="ob-ov" x-show="open" x-cloak @click.self="close()" x-transition.opacity role="dialog" aria-modal="true" aria-label="Order quick view">
    <div class="ob-panel" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-6">
        <div class="ob-bar">
            <button type="button" @click="step(-1)" :disabled="pos <= 0" title="Previous order (←)" aria-label="Previous order">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" @click="step(1)" :disabled="pos < 0 || pos >= ids.length - 1" title="Next order (→)" aria-label="Next order">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
            <span class="text-xs text-gray-400 font-medium" x-show="pos >= 0" x-text="(pos + 1) + ' of ' + ids.length"></span>
            <span class="text-xs text-gray-400 ob-hide-m ml-2">← → to move · Esc to close</span>
            <button type="button" @click="close()" class="ml-auto" title="Close (Esc)" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="ob-body" x-ref="body">
            <template x-if="loading && !html">
                <div><div class="ob-skel" style="height:3rem"></div><div class="ob-skel"></div><div class="ob-skel" style="height:10rem"></div><div class="ob-skel"></div></div>
            </template>
            <div x-html="html" :class="loading && 'ob-loading'"></div>
        </div>
    </div>
</div>
@endif
