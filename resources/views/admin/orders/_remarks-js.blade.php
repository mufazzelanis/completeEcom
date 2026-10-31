{{-- Styles + Alpine component for _remarks (orders list drawer, dashboard drawer, order page). --}}
<style>
    .rm-card { background: #fff; border: 1px solid #eef2f7; border-radius: 1rem; padding: .9rem 1rem; }
    .rm-h { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; display: inline-flex; align-items: center; gap: .4rem; }
    .rm-count { font-size: .68rem; padding: 0 .45rem; border-radius: 999px; background: #eef2ff; color: #4338ca; }
    .rm-compose { background: #f8fafc; border: 1px solid #eef2f7; border-radius: .9rem; padding: .6rem; margin-bottom: .75rem; }
    .rm-types, .rm-quick, .rm-outcomes, .rm-later { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rm-types { margin-bottom: .5rem; }
    .rm-types button { font-size: .75rem; font-weight: 700; padding: .3rem .6rem; border-radius: 999px; border: 1px solid #e5e7eb; background: #fff; color: #4b5563; }
    .rm-types button.is-on { background: var(--c); border-color: var(--c); color: #fff; }
    .rm-input { width: 100%; border: 1px solid #e5e7eb; border-radius: .7rem; padding: .5rem .65rem; font-size: .875rem; background: #fff; color: #111827; resize: vertical; }
    .rm-input:focus { outline: none; border-color: #818cf8; box-shadow: 0 0 0 3px rgba(129,140,248,.18); }
    .rm-quick { margin-top: .4rem; }
    .rm-quick button { font-size: .72rem; padding: .2rem .55rem; border-radius: 999px; background: #eef2ff; color: #4338ca; }
    .rm-quick button:hover { background: #e0e7ff; }
    .rm-outcomes { margin-top: .5rem; }
    .rm-outcomes button { flex: 1 1 auto; font-size: .78rem; font-weight: 800; padding: .5rem .6rem; border-radius: .7rem; border: 1.5px solid color-mix(in srgb, var(--c) 35%, transparent); background: #fff; color: var(--c); }
    .rm-outcomes button:hover, .rm-outcomes button.is-on { background: var(--c); color: #fff; }
    .rm-outcomes button:disabled { opacity: .5; }
    .rm-later { align-items: center; margin-top: .5rem; padding: .5rem; border-radius: .7rem; background: #fffbeb; }
    .rm-later > button:not(.rm-save) { font-size: .72rem; font-weight: 700; padding: .25rem .55rem; border-radius: 999px; background: #fff; border: 1px solid #fde68a; color: #92400e; }
    .rm-save { font-size: .8rem; font-weight: 800; padding: .45rem .9rem; border-radius: .7rem; background: #4f46e5; color: #fff; }
    .rm-save:disabled { opacity: .45; }
    .rm-list { display: flex; flex-direction: column; gap: .15rem; }
    .rm-item { display: flex; gap: .6rem; padding: .55rem .4rem; border-radius: .7rem; }
    .rm-item:hover { background: #f8fafc; }
    .rm-item.is-pinned { background: #fffbeb; border: 1px solid #fde68a; }
    .rm-av { width: 1.9rem; height: 1.9rem; border-radius: 999px; color: #fff; font-size: .68rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rm-who { font-size: .82rem; color: #111827; }
    .rm-tag { font-size: .68rem; font-weight: 800; padding: .08rem .45rem; border-radius: 999px; white-space: nowrap; }
    .rm-time { font-size: .7rem; color: #9ca3af; white-space: nowrap; }
    .rm-body { font-size: .85rem; color: #374151; margin-top: .15rem; white-space: pre-line; word-break: break-word; }
    .rm-follow { display: inline-block; margin-top: .3rem; font-size: .72rem; font-weight: 800; padding: .15rem .5rem; border-radius: .5rem; background: #fef3c7; color: #92400e; }
    .rm-follow.is-late { background: #fee2e2; color: #b91c1c; }
    .rm-acts { display: flex; gap: .1rem; opacity: .35; transition: opacity .15s; }
    .rm-item:hover .rm-acts, .rm-acts:focus-within { opacity: 1; }
    .rm-acts button { width: 1.7rem; height: 1.7rem; border-radius: .5rem; font-size: .8rem; }
    .rm-acts button:hover { background: #eef2f7; }
    .rm-event { display: flex; align-items: baseline; gap: .55rem; padding: .3rem .4rem; font-size: .78rem; color: #6b7280; }
    .rm-event b { color: #374151; font-weight: 600; }
    .rm-ev-ic { width: 1.9rem; text-align: center; flex-shrink: 0; font-size: .8rem; }
    .rm-sep { height: 1px; background: #f1f5f9; margin: .3rem 0; }
    .rm-more > summary { cursor: pointer; list-style: none; font-size: .75rem; font-weight: 700; color: #4f46e5; padding: .4rem; text-align: center; border-radius: .6rem; }
    .rm-more > summary::-webkit-details-marker { display: none; }
    .rm-more > summary:hover { background: #eef2ff; }
    .rm-more[open] > summary { display: none; }
    .dark .rm-card, .dark .rm-types button, .dark .rm-input, .dark .rm-outcomes button { background: #111827; border-color: #1f2937; color: #e5e7eb; }
    .dark .rm-compose { background: #0b1220; border-color: #1f2937; }
    .dark .rm-who, .dark .rm-event b { color: #f3f4f6; }
    .dark .rm-body { color: #d1d5db; }
    .dark .rm-item:hover { background: #1f2937; }
    .dark .rm-item.is-pinned { background: rgba(245,158,11,.1); border-color: rgba(245,158,11,.3); }
    @media (hover: none) { .rm-acts { opacity: .8; } }
</style>
<script>
    window.orderRemarks = function (cfg) {
        const pad = n => String(n).padStart(2, '0');
        const local = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        const toast = (ok, msg) => (window.ordersListToast ? ordersListToast(ok, msg) : (window.showToast && showToast(msg, ok ? 'success' : 'error')));
        return {
            type: 'call', outcome: null, body: '', followUp: '', busy: false, count: cfg.count,
            templates: {
                call: ['Rang twice', 'Will confirm by evening', 'Asked to deliver on Friday', 'Wants a different address'],
                courier: ['Booked with Steadfast', 'Booked with Pathao', 'Booked with RedX', 'Pickup tomorrow', 'Rider couldn\'t reach customer', 'Returned to us'],
                payment: ['bKash received ✓', 'Nagad received ✓', 'TrxID checked ✓', 'Amount doesn\'t match', 'Refund sent'],
                customer: ['Asked about delivery time', 'Asked for discount', 'Wants to change product', 'Complained about delay'],
                note: [],
            },
            get placeholder() {
                return { call: 'Optional note, then tap the call result below…', courier: 'Courier, tracking number, pickup…', payment: 'Amount, TrxID, method…', customer: 'What did the customer say?', note: 'Write a remark for the team…' }[this.type];
            },
            setType(t) { this.type = t; this.outcome = null; this.$nextTick(() => this.$refs.body.focus()); },
            later(when) {
                const d = new Date();
                if (when === 1) d.setHours(d.getHours() + 1);
                else if (when === '19') { d.setHours(19, 0, 0, 0); if (d < new Date()) d.setDate(d.getDate() + 1); }
                else { d.setDate(d.getDate() + 1); d.setHours(11, 0, 0, 0); }
                this.followUp = local(d);
            },
            async send(url, method, payload) {
                const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: payload ? JSON.stringify(payload) : undefined });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(d.message || 'Could not save — please try again.');
                this.$refs.list.innerHTML = d.html;
                this.count = d.count;
                // orders list row: remark count / latest line / call-back badge
                window.dispatchEvent(new CustomEvent('order-remark', { detail: { id: cfg.orderId, count: d.count, latest: d.latest, followUp: d.followUp } }));
                return d;
            },
            async add(outcome = null) {
                if (this.busy) return;
                if (outcome) this.outcome = outcome;
                if (this.outcome === 'call_later' && !this.followUp) { this.later('19'); return; } // pick a time first
                if (!this.body.trim() && !this.outcome) return;
                this.busy = true;
                try {
                    const d = await this.send(cfg.storeUrl, 'POST', { type: this.type, outcome: this.type === 'call' ? this.outcome : null, body: this.body, follow_up_at: this.outcome === 'call_later' ? this.followUp : null });
                    this.body = ''; this.outcome = null; this.followUp = '';
                    toast(true, '✓ ' + d.message);
                } catch (e) { toast(false, e.message); } finally { this.busy = false; }
            },
            async pin(url) {
                try { const d = await this.send(url, 'PATCH'); toast(true, d.message); } catch (e) { toast(false, e.message); }
            },
            async remove(url) {
                if (!(await showConfirmModal('Delete this remark?'))) return;
                try { const d = await this.send(url, 'DELETE'); toast(true, d.message); } catch (e) { toast(false, e.message); }
            },
        };
    };
</script>
