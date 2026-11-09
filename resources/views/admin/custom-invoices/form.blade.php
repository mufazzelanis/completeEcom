@extends('layouts.admin')
@section('title', $invoice->exists ? 'Edit ' . $invoice->number : 'New Custom Invoice')

@section('content')
{{-- Invoice builder: editor on the left, a live paper preview on the right (same layout as the
     PDF). Totals here are only a preview — the server recalculates everything on save
     (CustomInvoice::recalculate). Ctrl/⌘+S saves; leaving with unsaved changes asks first. --}}
<style>
    .ci-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
    @media (min-width: 1280px) { .ci-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); } .ci-sticky { position: sticky; top: 1rem; } }
    .ci-two { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; }
    @media (min-width: 640px) { .ci-two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .ci-four { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    @media (min-width: 640px) { .ci-four { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ci-line { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; align-items: start; }
    @media (max-width: 479px) { .ci-line { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .ci-in { width: 100%; border: 1px solid #e5e7eb; border-radius: .6rem; padding: .5rem .7rem; font-size: .875rem; background: transparent; }
    .ci-in:focus { outline: none; box-shadow: 0 0 0 2px #6366f1; border-color: transparent; }
    .ci-flex { display: flex; justify-content: space-between; gap: 8px; }
    .ci-xs { font-size: 11px; } .ci-xxs { font-size: 10px; }
    /* The preview is the PDF drawn to scale: every size is in mm or pt of an A4 sheet (see admin/invoices/_pdf-styles). */
    .ci-paper { background: #fff; color: #1f2937; border-radius: 14px; box-shadow: 0 10px 40px -12px rgba(15, 23, 42, .25); overflow: hidden; container-type: inline-size; }
    .pv { --mm: calc(100cqw / 210); --pt: calc(100cqw / 210 * .3528); position: relative; display: flex; flex-direction: column; min-height: calc(100cqw * 297 / 210); font-size: calc(var(--pt) * 10); line-height: 1.4; }
    .pv table { width: 100%; border-collapse: collapse; }
    .pv-band { height: calc(var(--mm) * 44); flex-shrink: 0; background: var(--dark); color: var(--ondark); border-top: calc(var(--mm) * 1.6) solid var(--acc); padding: calc(var(--mm) * 10.9) calc(var(--mm) * 12) 0; display: flex; justify-content: space-between; align-items: flex-start; }
    .pv-plate { background: #fff; border-radius: calc(var(--mm) * 2.5); padding: calc(var(--mm) * 3) calc(var(--mm) * 5); }
    .pv-plate img { height: calc(var(--mm) * 13); max-width: calc(var(--mm) * 58); object-fit: contain; display: block; }
    .pv-brand { font-size: calc(var(--pt) * 20); font-weight: 700; }
    .pv-doc { text-align: right; }
    .pv-title { font-size: calc(var(--pt) * 27); font-weight: 700; letter-spacing: calc(var(--pt) * 1.5); line-height: 1.05; }
    .pv-no { font-size: calc(var(--pt) * 11.5); font-weight: 700; color: var(--line); }
    .pv-body { flex: 1; display: flex; flex-direction: column; padding: 0 calc(var(--mm) * 12); position: relative; }
    .pv-label { font-size: calc(var(--pt) * 7.5); font-weight: 700; letter-spacing: calc(var(--pt) * .5); text-transform: uppercase; color: var(--acctext); }
    .pv-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: calc(var(--mm) * .7); margin-top: calc(var(--mm) * -8); }
    .pv-facts > div { background: var(--tint); padding: calc(var(--mm) * 3) calc(var(--mm) * 4); min-width: 0; }
    .pv-fact { font-size: calc(var(--pt) * 11); font-weight: 700; color: #111827; overflow-wrap: anywhere; }
    .pv-parties { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: calc(var(--mm) * 4); margin-top: calc(var(--mm) * 6); }
    .pv-parties > div { border: calc(var(--mm) * .25) solid #e5e7eb; padding: calc(var(--mm) * 4) calc(var(--mm) * 5); min-width: 0; }
    .pv-name { font-size: calc(var(--pt) * 13.5); font-weight: 700; color: #111827; }
    .pv-strong { font-weight: 700; color: #111827; }
    .pv-line { color: #4b5563; white-space: pre-line; overflow-wrap: anywhere; }
    .pv-items { margin-top: calc(var(--mm) * 6); }
    .pv-items th { background: var(--dark); color: var(--ondark); font-size: calc(var(--pt) * 8.5); text-transform: uppercase; padding: calc(var(--mm) * 2.8) calc(var(--mm) * 3); text-align: left; }
    .pv-items td { padding: calc(var(--mm) * 2.6) calc(var(--mm) * 3); border-bottom: calc(var(--mm) * .25) solid #e5e7eb; vertical-align: middle; }
    .pv-items .r, .ci-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .pv-items .c { text-align: center; }
    .pv-items img { width: calc(var(--mm) * 11); height: calc(var(--mm) * 11); object-fit: contain; display: block; margin: auto; }
    .pv-sub { font-size: calc(var(--pt) * 8.5); color: #6b7280; }
    .pv-summary { display: flex; justify-content: space-between; margin-top: calc(var(--mm) * 6); }
    .pv-left { width: 56%; }
    .pv-right { width: 40%; }
    .pv-panel { background: #f9fafb; border-left: calc(var(--mm) * .9) solid var(--acc); padding: calc(var(--mm) * 2.6) calc(var(--mm) * 4); margin-bottom: calc(var(--mm) * 2.5); }
    .pv-row { display: flex; justify-content: space-between; gap: 1em; padding: calc(var(--mm) * 1.8) calc(var(--mm) * 3); color: #4b5563; }
    .pv-row b { font-weight: 400; color: #111827; }
    .pv-grand { background: var(--dark); color: var(--ondark); font-size: calc(var(--pt) * 13.5); font-weight: 700; padding: calc(var(--mm) * 3); display: flex; justify-content: space-between; }
    .pv-due { background: var(--tint); color: var(--acctext); font-size: calc(var(--pt) * 11); font-weight: 700; padding: calc(var(--mm) * 2.6) calc(var(--mm) * 3); display: flex; justify-content: space-between; }
    .pv-paid { background: #ecfdf5; color: #047857; }
    .pv-push { flex: 1; min-height: calc(var(--mm) * 3); }
    .pv-boxes { display: flex; gap: calc(var(--mm) * 2.5); }
    .pv-boxes > div { flex: 1; min-width: 0; background: #f9fafb; padding: calc(var(--mm) * 2.8) calc(var(--mm) * 4); }
    .pv-note { font-size: calc(var(--pt) * 8.5); color: #4b5563; white-space: pre-line; overflow-wrap: anywhere; }
    .pv-closing { background: var(--tint); margin-top: calc(var(--mm) * 3); padding: calc(var(--mm) * 4) calc(var(--mm) * 5); display: flex; justify-content: space-between; align-items: center; gap: 1em; }
    .pv-thanks { font-size: calc(var(--pt) * 13); font-weight: 700; color: var(--acctext); }
    .pv-small { font-size: calc(var(--pt) * 8.5); color: #6b7280; }
    .pv-sign { text-align: center; font-size: calc(var(--pt) * 8); color: #6b7280; flex-shrink: 0; }
    .pv-sign img { height: calc(var(--mm) * 12); max-width: calc(var(--mm) * 40); object-fit: contain; display: block; margin: auto; }
    .pv-foot { display: flex; justify-content: space-between; font-size: calc(var(--pt) * 8); color: #6b7280; padding: calc(var(--mm) * 6) 0 calc(var(--mm) * 7); }
    .pv-stamp { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: calc(var(--pt) * 90); font-weight: 700; color: #6b7280; opacity: .1; pointer-events: none; white-space: nowrap; }
    .ci-drop { position: absolute; z-index: 30; left: 0; right: 0; margin-top: 4px; max-height: 18rem; overflow-y: auto; border-radius: .75rem; box-shadow: 0 12px 32px -8px rgba(0,0,0,.25); }
</style>
@php
    $currency = setting('currency_symbol', '৳');
    // the same details and colours the PDF prints, so the preview can't drift from it
    $store = \App\Support\InvoicePdf::store();
    $theme = \App\Support\InvoicePdf::theme(null);
    $showPhotos = \App\Support\InvoicePdf::showPhotos();
    $lines = collect(old('items', $invoice->items ?: []));
    $productImages = \App\Models\Product::whereIn('id', $lines->pluck('product_id')->filter())->pluck('image', 'id');
    $types = collect(\App\Models\CustomInvoice::TYPES)->map(fn ($t) => ['title' => $t[0], 'label' => $t[1], 'color' => $t[2]]);
    $state = [
        'type' => old('type', $invoice->type ?: 'invoice'),
        'status' => old('status', $invoice->status ?: 'unpaid'),
        'user_id' => old('user_id', $invoice->user_id),
        'customer_name' => (string) old('customer_name', $invoice->customer_name),
        'customer_phone' => old('customer_phone', $invoice->customer_phone),
        'customer_email' => old('customer_email', $invoice->customer_email),
        'customer_address' => old('customer_address', $invoice->customer_address),
        'issue_date' => old('issue_date', optional($invoice->issue_date)->format('Y-m-d') ?: now()->format('Y-m-d')),
        'due_date' => old('due_date', optional($invoice->due_date)->format('Y-m-d')),
        'items' => $lines->map(fn ($i) => [
            'product_id' => $i['product_id'] ?? null, 'name' => $i['name'] ?? '', 'description' => $i['description'] ?? '',
            'qty' => (float) ($i['qty'] ?? 1), 'price' => (float) ($i['price'] ?? 0), 'discount' => (float) ($i['discount'] ?? 0),
            'image' => !empty($i['product_id']) && !empty($productImages[$i['product_id']]) ? image_thumb($productImages[$i['product_id']], 96) : null,
        ])->values()->all(),
        'discount_type' => old('discount_type', $invoice->discount_type ?: 'fixed'),
        'discount_value' => (float) old('discount_value', $invoice->discount_value ?? 0),
        'shipping' => (float) old('shipping', $invoice->shipping ?? 0),
        'tax_rate' => (float) old('tax_rate', $invoice->tax_rate ?? 0),
        'paid_amount' => (float) old('paid_amount', $invoice->paid_amount ?? 0),
        'payment_method' => old('payment_method', $invoice->payment_method),
        'notes' => old('notes', $invoice->notes),
        'terms' => old('terms', $invoice->terms),
        'accent_color' => old('accent_color', $invoice->accent_color ?: setting('invoice_accent_color', '#6366f1')),
    ];
@endphp

<script>
function invoiceBuilder(state, types, currency, statuses, currencyRight) {
    let k = 0;
    const blank = () => ({ _k: ++k, product_id: null, name: '', description: '', qty: 1, price: 0, discount: 0, image: null, _open: false, _results: [], _t: null });
    state.items = state.items.length ? state.items.map(i => Object.assign(blank(), i)) : [blank()];
    const round = n => Math.round((Number(n) || 0) * 100) / 100;

    return {
        s: state, types, currency, dirty: false,
        cust: { q: '', results: [], open: false, loading: false },
        init() {
            this.$nextTick(() => this.$watch('s', () => { this.dirty = true; }, { deep: true }));
            window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
            document.addEventListener('turbo:before-visit', (e) => { if (this.dirty && !confirm('Leave without saving this invoice?')) e.preventDefault(); }, { once: true });
        },
        isQuote() { return ['quotation', 'proforma'].includes(this.s.type); },
        setType(t) { this.s.type = t; if (t === 'receipt' && !this.s.paid_amount) this.s.paid_amount = this.total(); },
        fmt(n) { const v = round(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); return currencyRight ? v + ' ' + currency : currency + v; },
        nice(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'; },
        // the PDF's colours worked out live, same formulas as hex_shade() and darken_for_white_text()
        tint(hex, p) { const c = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16)); return '#' + c.map(v => Math.round(v + (255 - v) * p).toString(16).padStart(2, '0')).join(''); },
        readable(hex) {
            let c = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16));
            const lum = a => { const [r, g, b] = a.map(v => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; }); return .2126 * r + .7152 * g + .0722 * b; };
            for (let i = 0; i < 40 && 1.05 / (lum(c) + .05) < 4.5; i++) c = c.map(v => Math.floor(v * .95));
            return '#' + c.map(v => v.toString(16).padStart(2, '0')).join('');
        },
        paperVars() { const a = /^#[0-9a-f]{6}$/i.test(this.s.accent_color) ? this.s.accent_color : '#6366f1'; return `--acc:${a};--tint:${this.tint(a, .92)};--line:${this.tint(a, .75)};--acctext:${this.readable(a)}`; },
        statusKey() {
            if (['draft', 'cancelled'].includes(this.s.status)) return this.s.status;
            const paid = Number(this.s.paid_amount) || 0;
            return paid <= 0 ? 'unpaid' : (paid >= this.total() ? 'paid' : 'partial');
        },
        statusInfo() { const k = this.statusKey(); return this.isQuote() && ['unpaid', 'partial', 'paid'].includes(k) ? ['Open', '#0ea5e9'] : statuses[k]; },
        stamp() { const k = this.statusKey(); return k === 'cancelled' ? 'CANCELLED' : k === 'draft' ? 'DRAFT' : k === 'paid' && !this.isQuote() ? 'PAID' : ''; },
        paysShown() { return !this.isQuote() && !['draft', 'cancelled'].includes(this.statusKey()); },
        filled() { return this.s.items.filter(i => i.name.trim()); },
        words(n) {
            const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            const small = v => v < 20 ? ones[v] : v < 100 ? (tens[Math.floor(v / 10)] + ' ' + ones[v % 10]).trim() : (ones[Math.floor(v / 100)] + ' Hundred ' + (v % 100 ? small(v % 100) : '')).trim();
            let whole = Math.floor(n); const paisa = Math.round((n - whole) * 100), parts = [];
            [[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand']].forEach(([size, name]) => { const q = Math.floor(whole / size); whole %= size; if (q) parts.push(small(q) + ' ' + name); });
            if (whole) parts.push(small(whole));
            return (parts.join(' ') || 'Zero') + (paisa > 0 ? ' and ' + small(paisa) + ' Poisha' : '');
        },
        shortName() { const n = this.s.customer_name.trim() || 'Customer'; return n.length > 40 ? n.slice(0, 40) + '...' : n; },
        dueIn(days) {
            if (!days) { this.s.due_date = ''; return; }
            const d = new Date((this.s.issue_date || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
            d.setDate(d.getDate() + days);
            this.s.due_date = d.toISOString().slice(0, 10);
        },
        lineTotal(i) { return Math.max(0, round((Number(i.qty) || 0) * (Number(i.price) || 0) - (Number(i.discount) || 0))); },
        subtotal() { return round(this.s.items.filter(i => i.name.trim()).reduce((t, i) => t + this.lineTotal(i), 0)); },
        discount() {
            const v = Math.max(0, Number(this.s.discount_value) || 0), sub = this.subtotal();
            return this.s.discount_type === 'percent' ? round(sub * Math.min(100, v) / 100) : Math.min(sub, round(v));
        },
        tax() { return round((this.subtotal() - this.discount()) * Math.min(100, Math.max(0, Number(this.s.tax_rate) || 0)) / 100); },
        total() { return round(this.subtotal() - this.discount() + this.tax() + Math.max(0, Number(this.s.shipping) || 0)); },
        due() { return Math.max(0, round(this.total() - Math.min(Number(this.s.paid_amount) || 0, this.total()))); },
        addItem() {
            this.s.items.push(blank());
            this.$nextTick(() => { const rows = this.$el.querySelectorAll('.ci-name input[type=text]:first-of-type'); rows[rows.length - 1]?.focus(); });
        },
        removeItem(i) { if (this.s.items.length > 1) this.s.items.splice(i, 1); },
        async lookup(q, kind) {
            const r = await fetch('{{ route('admin.custom-invoices.lookup') }}?kind=' + kind + '&q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            return r.ok ? r.json() : { products: [], customers: [] };
        },
        findProducts(item) {
            clearTimeout(item._t);
            if (item.name.trim().length < 2) { item._open = false; return; }
            item._t = setTimeout(async () => {
                try { item._results = (await this.lookup(item.name, 'products')).products; item._open = item._results.length > 0; } catch (e) {}
            }, 250);
        },
        pickProduct(item, p) {
            Object.assign(item, { product_id: p.id, name: p.name, price: p.price, image: p.image, description: item.description || (p.sku ? 'SKU: ' + p.sku : ''), _open: false });
        },
        async findCustomers() {
            if (this.cust.q.trim().length < 2) { this.cust.open = false; return; }
            this.cust.loading = true;
            try { this.cust.results = (await this.lookup(this.cust.q, 'customers')).customers; } catch (e) { this.cust.results = []; }
            this.cust.loading = false; this.cust.open = true;
        },
        pickCustomer(c) {
            Object.assign(this.s, { user_id: c.id, customer_name: c.name, customer_phone: c.phone || '', customer_email: c.email || '', customer_address: c.address || '' });
            this.cust.q = ''; this.cust.open = false;
        },
        // flat name => value map posted with the form
        fields() {
            const f = {};
            ['type', 'status', 'user_id', 'customer_name', 'customer_phone', 'customer_email', 'customer_address', 'issue_date', 'due_date',
             'discount_type', 'discount_value', 'shipping', 'tax_rate', 'paid_amount', 'payment_method', 'notes', 'terms', 'accent_color']
                .forEach(key => f[key] = this.s[key] ?? '');
            if (this.isQuote()) f.paid_amount = 0;
            this.s.items.filter(i => i.name.trim()).forEach((i, n) => {
                ['product_id', 'name', 'description', 'qty', 'price', 'discount'].forEach(key => f['items[' + n + '][' + key + ']'] = i[key] ?? '');
            });
            return f;
        },
        save() {
            if (!this.s.customer_name.trim()) { alert('Customer name is required.'); return; }
            if (!this.s.items.some(i => i.name.trim())) { alert('Add at least one item.'); return; }
            this.$nextTick(() => { this.dirty = false; this.$refs.form.requestSubmit ? this.$refs.form.requestSubmit() : this.$refs.form.submit(); });
        },
    };
}
</script>

<form method="POST" x-ref="form"
      action="{{ $invoice->exists ? route('admin.custom-invoices.update', $invoice) : route('admin.custom-invoices.store') }}"
      x-data="invoiceBuilder(@js($state), @js($types), @js($currency), @js(\App\Models\CustomInvoice::STATUSES), @js(setting('currency_position', 'left') === 'right'))"
      @submit="dirty = false" @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
      @keydown.window.ctrl.s.prevent="save()" @keydown.window.meta.s.prevent="save()">
    @csrf
    @if($invoice->exists) @method('PUT') @endif

    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ $invoice->exists ? route('admin.custom-invoices.show', $invoice) : route('admin.custom-invoices.index') }}" class="w-9 h-9 bg-white dark:bg-gray-900 rounded-xl shadow-sm flex items-center justify-center text-gray-500 hover:text-indigo-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                    {{ $invoice->exists ? 'Edit' : 'New' }} <span x-text="types[s.type].label"></span>
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $nextNumber }}@unless($invoice->exists) <span class="font-sans text-gray-400">(number is assigned on save)</span>@endunless</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span x-show="dirty" x-cloak class="text-xs text-amber-600">● Unsaved changes</span>
            <button type="button" @click="s.status = 'draft'; save()" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200">Save as draft</button>
            <button type="button" @click="if (s.status === 'draft') s.status = 'unpaid'; save()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" :style="{ background: s.accent_color }">
                Save <span x-text="types[s.type].label.toLowerCase()"></span> <span class="opacity-70 ci-xs ml-1 hidden sm:inline">Ctrl+S</span>
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- every field the server needs, kept in sync with the builder state --}}
    <template x-for="(v, k) in fields()" :key="k"><input type="hidden" :name="k" :value="v"></template>

    <div class="ci-grid">
        {{-- ════════ editor ════════ --}}
        <div class="space-y-5 min-w-0">
            {{-- document type --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <div class="ci-four">
                    <template x-for="(t, key) in types" :key="key">
                        <button type="button" @click="setType(key)"
                                class="rounded-xl px-3 py-3 text-left transition border-2"
                                :style="s.type === key ? 'border-color:' + t.color + ';background:' + t.color + '14' : 'border-color:transparent;background:rgba(148,163,184,.08)'">
                            <span class="block w-2.5 h-2.5 rounded-full mb-2" :style="{ background: t.color }"></span>
                            <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="t.label"></span>
                            <span class="block ci-xxs text-gray-400" x-text="{invoice: 'Bill a customer', quotation: 'Price offer', proforma: 'Before payment', receipt: 'Payment received'}[key]"></span>
                        </button>
                    </template>
                </div>
                <div class="ci-four mt-4">
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Date</label>
                        <input type="date" x-model="s.issue_date" class="ci-in" required>
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block" x-text="isQuote() ? 'Valid until' : 'Due date'"></label>
                        <input type="date" x-model="s.due_date" :min="s.issue_date" class="ci-in">
                    </div>
                    <div class="col-span-2">
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Quick due date</label>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="d in [0, 7, 15, 30]" :key="d">
                                <button type="button" @click="dueIn(d)" class="px-2.5 py-1.5 rounded-lg ci-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-indigo-50 hover:text-indigo-600"
                                        x-text="d === 0 ? 'None' : d + ' days'"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- customer --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Customer</h3>
                    <button type="button" x-show="s.user_id" x-cloak @click="s.user_id = null" class="ci-xs text-gray-400 hover:text-red-500">Unlink account ✕</button>
                </div>
                <div class="relative" @click.outside="cust.open = false">
                    <input type="text" x-model="cust.q" @input.debounce.300ms="findCustomers()" @focus="if (cust.results.length) cust.open = true"
                           placeholder="🔎 Find an existing customer by name, phone or email…" class="ci-in" autocomplete="off">
                    <div x-show="cust.open" x-cloak class="ci-drop bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 py-1">
                        <template x-for="c in cust.results" :key="c.id">
                            <button type="button" @click="pickCustomer(c)" class="w-full text-left px-4 py-2 hover:bg-indigo-50 dark:hover:bg-gray-700">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="c.name"></span>
                                <span class="ci-xs text-gray-400 font-mono" x-text="c.phone ? ' · ' + c.phone : ''"></span>
                                <span class="block ci-xs text-gray-400 truncate" x-text="c.address || c.email || ''"></span>
                            </button>
                        </template>
                        <p x-show="!cust.loading && !cust.results.length" class="px-4 py-2 text-sm text-gray-400">No matching customer — just type the details below.</p>
                    </div>
                </div>
                <div class="ci-two">
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="s.customer_name" class="ci-in" required maxlength="255">
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Phone</label>
                        <input type="text" x-model="s.customer_phone" class="ci-in font-mono" maxlength="40">
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Email</label>
                        <input type="email" x-model="s.customer_email" class="ci-in" maxlength="255">
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Address</label>
                        <textarea x-model="s.customer_address" rows="1" class="ci-in" maxlength="1000"></textarea>
                    </div>
                </div>
            </div>

            {{-- items --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Items <span class="text-gray-400 font-normal text-sm" x-text="'(' + s.items.length + ')'"></span></h3>
                    <span class="ci-xs text-gray-400">Type to search the catalog, or write anything for a custom line</span>
                </div>
                <div class="space-y-3">
                    <template x-for="(item, i) in s.items" :key="item._k">
                        <div class="rounded-xl p-3 border border-gray-100 dark:border-gray-800" :style="{ borderLeft: '3px solid ' + (item.product_id ? '#6366f1' : s.accent_color + '66') }">
                            <div class="ci-name relative" @click.outside="item._open = false">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 flex-shrink-0 rounded-full flex items-center justify-center ci-xs font-bold text-white" :style="{ background: s.accent_color }" x-text="i + 1"></span>
                                    <template x-if="item.image"><img :src="item.image" alt="" class="w-9 h-9 rounded-lg object-contain bg-gray-50 flex-shrink-0"></template>
                                    <input type="text" x-model="item.name" @input="item.product_id = null; item.image = null; findProducts(item)" @focus="if (item._results?.length) item._open = true"
                                           @keydown.escape="item._open = false" placeholder="Product or service name" class="ci-in font-medium" maxlength="255" autocomplete="off">
                                    <button type="button" @click="removeItem(i)" class="flex-shrink-0 p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50" title="Remove line" x-show="s.items.length > 1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <input type="text" x-model="item.description" placeholder="Description (optional) — size, colour, warranty…" class="ci-in mt-1 ci-xs" maxlength="500" style="padding-top:.35rem;padding-bottom:.35rem">
                                <span x-show="item.product_id" x-cloak class="inline-block mt-1 ci-xxs font-semibold rounded-full px-2 py-0.5" style="background:#6366f11a;color:#6366f1">From catalog</span>
                                <div x-show="item._open" x-cloak class="ci-drop bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 py-1">
                                    <template x-for="p in item._results" :key="p.id">
                                        <button type="button" @click="pickProduct(item, p)" class="w-full flex items-center gap-3 text-left px-3 py-2 hover:bg-indigo-50 dark:hover:bg-gray-700">
                                            <img :src="p.image || ''" x-show="p.image" alt="" class="w-10 h-10 rounded-lg object-contain bg-gray-50 flex-shrink-0">
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm text-gray-800 dark:text-gray-100 truncate" x-text="p.name"></span>
                                                <span class="block ci-xs text-gray-400" x-text="(p.sku ? 'SKU ' + p.sku + ' · ' : '') + (p.stock > 0 ? p.stock + ' in stock' : 'Out of stock')"></span>
                                            </span>
                                            <span class="text-right flex-shrink-0">
                                                <span class="block text-sm font-bold" style="color:#6366f1" x-text="fmt(p.price)"></span>
                                                <span x-show="p.regular > p.price" class="block ci-xs text-gray-400 line-through" x-text="fmt(p.regular)"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="ci-line mt-2">
                                <label><span class="block ci-xxs uppercase text-gray-400 mb-0.5">Qty</span><input type="number" x-model.number="item.qty" min="0" step="any" class="ci-in text-center"></label>
                                <label><span class="block ci-xxs uppercase text-gray-400 mb-0.5">Unit price</span><input type="number" x-model.number="item.price" min="0" step="any" class="ci-in text-right"></label>
                                <label><span class="block ci-xxs uppercase text-gray-400 mb-0.5">Discount</span><input type="number" x-model.number="item.discount" min="0" step="any" class="ci-in text-right" placeholder="0"></label>
                                <div><span class="block ci-xxs uppercase text-gray-400 mb-0.5 text-right">Amount</span><div class="ci-num font-bold text-gray-900 dark:text-gray-100 pt-2" x-text="fmt(lineTotal(item))"></div></div>
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" @click="addItem()" class="mt-3 w-full rounded-xl border-2 border-dashed py-2.5 text-sm font-medium transition hover:opacity-80"
                        :style="'border-color:' + s.accent_color + '55;color:' + s.accent_color">+ Add line</button>
            </div>

            {{-- totals & payment --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 space-y-4">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Totals &amp; payment</h3>
                <div class="ci-four">
                    <div class="col-span-2">
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Discount</label>
                        <div class="flex gap-1">
                            <input type="number" x-model.number="s.discount_value" min="0" step="any" class="ci-in text-right">
                            <div class="flex rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                <button type="button" @click="s.discount_type = 'fixed'" class="px-3 text-sm" :style="s.discount_type === 'fixed' ? 'background:' + s.accent_color + ';color:#fff' : ''" x-text="currency"></button>
                                <button type="button" @click="s.discount_type = 'percent'" class="px-3 text-sm" :style="s.discount_type === 'percent' ? 'background:' + s.accent_color + ';color:#fff' : ''">%</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">VAT / Tax %</label>
                        <input type="number" x-model.number="s.tax_rate" min="0" max="100" step="any" class="ci-in text-right">
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Delivery</label>
                        <input type="number" x-model.number="s.shipping" min="0" step="any" class="ci-in text-right">
                    </div>
                </div>
                <div class="ci-four" x-show="!isQuote()">
                    <div class="col-span-2">
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Amount paid</label>
                        <div class="flex gap-1">
                            <input type="number" x-model.number="s.paid_amount" min="0" step="any" class="ci-in text-right">
                            <button type="button" @click="s.paid_amount = total()" class="px-3 rounded-xl text-sm font-medium flex-shrink-0" style="background:#10b9811a;color:#059669">Full</button>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Payment method</label>
                        <input type="text" x-model="s.payment_method" list="ci-methods" class="ci-in" maxlength="40" placeholder="Cash, bKash…">
                        <datalist id="ci-methods">@foreach(\App\Models\CustomInvoice::PAYMENT_METHODS as $m)<option value="{{ $m }}">@endforeach</datalist>
                    </div>
                </div>
                <div class="rounded-xl p-4 space-y-1.5 text-sm" :style="{ background: s.accent_color + '0d' }">
                    <div class="flex justify-between text-gray-600 dark:text-gray-300"><span>Subtotal</span><span class="tabular-nums" x-text="fmt(subtotal())"></span></div>
                    <div class="flex justify-between" style="color:#dc2626" x-show="discount() > 0"><span>Discount</span><span class="tabular-nums" x-text="'-' + fmt(discount())"></span></div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-300" x-show="tax() > 0"><span x-text="'VAT / Tax (' + s.tax_rate + '%)'"></span><span class="tabular-nums" x-text="fmt(tax())"></span></div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-300" x-show="s.shipping > 0"><span>Delivery</span><span class="tabular-nums" x-text="fmt(s.shipping)"></span></div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100"><span>Total</span><span class="tabular-nums" x-text="fmt(total())"></span></div>
                    <div class="flex justify-between font-semibold" x-show="!isQuote() && total() > 0" :style="{ color: (due() > 0 ? '#ef4444' : '#059669') }">
                        <span x-text="due() > 0 ? 'Balance due' : '✓ Fully paid'"></span><span class="tabular-nums" x-text="fmt(due())"></span>
                    </div>
                </div>
            </div>

            {{-- notes, terms, look --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 space-y-3">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Notes &amp; look</h3>
                <div class="ci-two">
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Notes <span class="normal-case text-gray-400">(shown on the invoice)</span></label>
                        <textarea x-model="s.notes" rows="3" class="ci-in" maxlength="3000" placeholder="e.g. Delivered by hand · Warranty 6 months"></textarea>
                    </div>
                    <div>
                        <label class="ci-xs font-medium text-gray-500 uppercase mb-1 block">Terms &amp; conditions</label>
                        <textarea x-model="s.terms" rows="3" class="ci-in" maxlength="3000"></textarea>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="ci-xs font-medium text-gray-500 uppercase">Colour</span>
                    <template x-for="c in ['#6366f1', '#0ea5e9', '#10b981', '#f97316', '#e11d48', '#7c3aed', '#111827']" :key="c">
                        <button type="button" @click="s.accent_color = c" class="w-7 h-7 rounded-full transition"
                                :style="{ background: c, boxShadow: s.accent_color === c ? '0 0 0 3px #fff, 0 0 0 5px ' + c : 'none' }" :title="c"></button>
                    </template>
                    <input type="color" x-model="s.accent_color" class="w-8 h-8 rounded cursor-pointer bg-transparent" title="Custom colour">
                    <label class="ml-auto flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        Status
                        <select x-model="s.status" class="ci-in" style="width:auto">
                            @foreach(\App\Models\CustomInvoice::STATUSES as $key => [$label])
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <p class="ci-xs text-gray-400">Unpaid / partially paid / paid is worked out from the amount paid; pick Draft or Cancelled to set it yourself.</p>
            </div>
        </div>

        {{-- ════════ live preview ════════ --}}
        <div class="ci-sticky min-w-0">
            <div class="flex items-center justify-between mb-2">
                <span class="ci-xs font-semibold uppercase tracking-wider text-gray-400">Live preview</span>
                <a href="{{ route('admin.business.index') }}" class="ci-xs text-indigo-500 hover:underline">Edit shop name, phone &amp; address →</a>
            </div>
            {{-- the fixed colours sit on the parent: a string :style would replace this element's own style --}}
            <div class="ci-paper" style="--dark:{{ $theme['dark'] }};--ondark:{{ $theme['onDark'] }};">
                <div class="pv" :style="paperVars()">
                    <div class="pv-band">
                        @if ($store['logo'])
                            <div class="pv-plate"><img src="{{ $store['logo'] }}" alt=""></div>
                        @else
                            <div class="pv-brand">{{ $store['name'] }}</div>
                        @endif
                        <div class="pv-doc">
                            <div class="pv-title" x-text="types[s.type].title"></div>
                            <div class="pv-no">{{ $nextNumber }}</div>
                        </div>
                    </div>
                    <div class="pv-body">
                        <div class="pv-facts">
                            <div><div class="pv-label">Date</div><div class="pv-fact" x-text="nice(s.issue_date)"></div></div>
                            <div><div class="pv-label" x-text="isQuote() ? 'Valid Until' : 'Due Date'"></div><div class="pv-fact" x-text="nice(s.due_date)"></div></div>
                            <div><div class="pv-label">Payment Method</div><div class="pv-fact" x-text="s.payment_method || '—'"></div></div>
                            <div><div class="pv-label">Status</div><div class="pv-fact" :style="{ color: readable(statusInfo()[1]) }" x-text="statusInfo()[0]"></div></div>
                        </div>
                        <div class="pv-parties">
                            <div>
                                <div class="pv-label" x-text="isQuote() ? 'Prepared For' : 'Bill To'"></div>
                                <div class="pv-name" :style="{ color: s.customer_name ? '#111827' : '#d1d5db' }" x-text="s.customer_name || 'Customer name'"></div>
                                <div class="pv-strong" x-show="s.customer_phone" x-text="s.customer_phone"></div>
                                <div class="pv-line" x-show="s.customer_address" x-text="s.customer_address"></div>
                                <div class="pv-line" x-show="s.customer_email" x-text="s.customer_email"></div>
                            </div>
                            <div>
                                <div class="pv-label">From</div>
                                <div class="pv-name">{{ $store['name'] }}</div>
                                @if ($store['phone'])<div class="pv-strong">{{ $store['phone'] }}</div>@endif
                                <div class="pv-line">{{ $store['address'] }}</div>
                                @if ($store['email'])<div class="pv-line">{{ $store['email'] }}</div>@endif
                                @if ($store['tax'])<div class="pv-line">{{ $store['tax'] }}</div>@endif
                            </div>
                        </div>
                        <table class="pv-items">
                            <thead><tr>
                                <th class="c" style="width:7%">#</th>
                                @if ($showPhotos)<th x-show="filled().some(i => i.image)" style="width:9%"></th>@endif
                                <th>Description</th>
                                <th class="c" style="width:8%">Qty</th>
                                <th class="r" style="width:15%">Unit Price</th>
                                <th class="r" x-show="filled().some(i => i.discount > 0)" style="width:12%">Discount</th>
                                <th class="r" style="width:16%">Amount</th>
                            </tr></thead>
                            <tbody>
                                <template x-for="(item, i) in filled()" :key="item._k">
                                    <tr :style="i % 2 ? 'background:#f9fafb' : ''">
                                        <td class="c" x-text="i + 1"></td>
                                        @if ($showPhotos)<td x-show="filled().some(l => l.image)" style="padding-top:0;padding-bottom:0"><img x-show="item.image" :src="item.image || ''" alt=""></td>@endif
                                        <td>
                                            <div class="pv-strong" x-text="item.name"></div>
                                            <div class="pv-sub" x-show="item.description" x-text="item.description"></div>
                                        </td>
                                        <td class="c" x-text="item.qty"></td>
                                        <td class="r" x-text="fmt(item.price)"></td>
                                        <td class="r" x-show="filled().some(l => l.discount > 0)" style="color:#dc2626" x-text="item.discount > 0 ? '-' + fmt(item.discount) : '—'"></td>
                                        <td class="r pv-strong" x-text="fmt(lineTotal(item))"></td>
                                    </tr>
                                </template>
                                <tr x-show="!filled().length"><td colspan="6" style="padding:calc(var(--mm) * 8);text-align:center;color:#d1d5db">Items you add appear here</td></tr>
                            </tbody>
                        </table>
                        <div class="pv-summary">
                            <div class="pv-left">
                                @if ($store['showWords'])
                                    <div class="pv-panel"><div class="pv-label">Amount in Words</div><div class="pv-strong"><span x-text="words(total())"></span> {{ $store['currencyWord'] }} Only</div></div>
                                @endif
                            </div>
                            <div class="pv-right">
                                <div class="pv-row"><span>Subtotal</span><b class="ci-num" x-text="fmt(subtotal())"></b></div>
                                <div class="pv-row" x-show="discount() > 0"><span x-text="'Discount' + (s.discount_type === 'percent' ? ' (' + s.discount_value + '%)' : '')"></span><b class="ci-num" style="color:#dc2626" x-text="'-' + fmt(discount())"></b></div>
                                <div class="pv-row" x-show="tax() > 0"><span x-text="'Tax / VAT (' + s.tax_rate + '%)'"></span><b class="ci-num" x-text="fmt(tax())"></b></div>
                                <div class="pv-row" x-show="s.shipping > 0"><span>Delivery Charge</span><b class="ci-num" x-text="fmt(s.shipping)"></b></div>
                                <div class="pv-grand"><span>Total</span><span class="ci-num" x-text="fmt(total())"></span></div>
                                <div class="pv-row" x-show="paysShown() && s.paid_amount > 0"><span>Paid</span><b class="ci-num" x-text="fmt(Math.min(s.paid_amount || 0, total()))"></b></div>
                                <div class="pv-due" x-show="paysShown() && due() > 0"><span>Balance Due</span><span class="ci-num" x-text="fmt(due())"></span></div>
                                <div class="pv-due pv-paid" x-show="paysShown() && total() > 0 && due() <= 0"><span>Paid in Full</span><span class="ci-num" x-text="fmt(total())"></span></div>
                            </div>
                        </div>
                        <div class="pv-push"></div>
                        <div class="pv-boxes" x-show="s.notes || s.terms || {{ $store['bank'] ? '!isQuote()' : 'false' }}">
                            <div x-show="s.notes"><div class="pv-label">Notes</div><div class="pv-note" x-text="s.notes"></div></div>
                            <div x-show="s.terms"><div class="pv-label">Terms &amp; Conditions</div><div class="pv-note" x-text="s.terms"></div></div>
                            @if ($store['bank'])<div x-show="!isQuote()"><div class="pv-label">Bank / Payment Details</div><div class="pv-note">{{ $store['bank'] }}</div></div>@endif
                        </div>
                        <div class="pv-closing">
                            <div>
                                <div class="pv-thanks" x-text="(isQuote() ? 'Thank you for considering us, ' : 'Thank you for your business, ') + shortName() + '!'"></div>
                                <div class="pv-small">{{ $store['footer'] }}</div>
                                @if ($store['phone'] || $store['email'])<div class="pv-small">Need help? <b>{{ collect([$store['phone'], $store['email']])->filter()->implode('  ·  ') }}</b></div>@endif
                            </div>
                            @if ($store['signature'])<div class="pv-sign"><img src="{{ $store['signature'] }}" alt="">Authorized Signature</div>@endif
                        </div>
                        <div class="pv-foot">
                            <span>{{ collect([$store['name'], $store['phone'], $store['email']])->filter()->implode('  ·  ') }}</span>
                            <span>Page 1 of 1</span>
                        </div>
                    </div>
                    <div class="pv-stamp" x-show="stamp()" x-text="stamp()"></div>
                </div>
            </div>
        </div>
    </div>
</form>


@endsection
