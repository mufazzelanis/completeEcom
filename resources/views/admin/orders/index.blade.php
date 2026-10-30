@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    // every generated link carries `sort`, so it is never a bare URL (a bare URL brings the
    // remembered filters back — see OrderController::index())
    $link = function (array $set = [], array $drop = []) {
        $q = array_merge(request()->except(array_merge($drop, ['page'])), $set);
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '');
        $q['sort'] = $q['sort'] ?? 'latest';
        return route('admin.orders.index', $q);
    };
    $tabs = ['' => 'All', 'pending' => 'Pending', 'processing' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
    $tabColor = ['pending' => '#d97706', 'processing' => '#2563eb', 'shipped' => '#7c3aed', 'delivered' => '#059669', 'cancelled' => '#dc2626', 'refunded' => '#6b7280'];
    $allCount = $statusCounts->sum();
    $ranges = ['' => 'Any time', 'today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month', 'custom' => 'Custom dates…'];
    $range = request('range') ?: (request('from') || request('to') ? 'custom' : '');
    $chips = array_filter([
        'search' => request('search') ? '“' . request('search') . '”' : null,
        'range' => request('range') ? $ranges[request('range')] ?? null : null,
        'from' => request('from') ? 'From ' . request('from') : null,
        'to' => request('to') ? 'To ' . request('to') : null,
        'payment_status' => request('payment_status') ? 'Payment: ' . ucfirst(request('payment_status')) : null,
        'payment_method' => request('payment_method') ? \App\Services\Notifications\OrderEmail::paymentLabel(request('payment_method')) : null,
        'fraud' => request('fraud') ? 'Fraud flagged' : null,
        'preorder' => request('preorder') ? 'Pre-orders' : null,
        'source' => request('source') ? ucfirst(request('source')) . ' orders' : null,
    ]);
@endphp

<style>
    .ob-stat { display: flex; flex-direction: column; gap: .1rem; padding: .8rem 1rem; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.04); border: 1px solid #f1f5f9; min-width: 0; transition: transform .15s, box-shadow .15s; }
    a.ob-stat:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.06); }
    .ob-stat b { font-size: 1.25rem; font-weight: 800; color: #111827; line-height: 1.2; }
    .ob-stat span { font-size: .72rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    .ob-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; }
    .ob-tabs { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding-bottom: 2px; }
    .ob-tabs::-webkit-scrollbar { display: none; }
    .ob-tab { flex-shrink: 0; display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .85rem; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #4b5563; background: #fff; border: 1px solid #e5e7eb; white-space: nowrap; }
    .ob-tab:hover { border-color: #fdba74; }
    .ob-tab.is-on { background: #111827; color: #fff; border-color: #111827; }
    .ob-tab i { font-style: normal; font-size: .72rem; font-weight: 700; padding: .05rem .45rem; border-radius: 999px; background: #f3f4f6; color: #374151; }
    .ob-tab.is-on i { background: rgba(255,255,255,.2); color: #fff; }
    .ob-in { border: 1px solid #e5e7eb; background: #f9fafb; color: #1f2937; border-radius: .75rem; padding: .5rem .75rem; font-size: .875rem; }
    .ob-in:focus { outline: none; border-color: #fb923c; box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
    .ob-in.is-set { border-color: #fdba74; background: #fff7ed; color: #9a3412; font-weight: 600; }
    .ob-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .35rem .2rem .65rem; border-radius: 999px; background: #fff7ed; color: #c2410c; font-size: .75rem; font-weight: 600; }
    .ob-chip a { width: 1.1rem; height: 1.1rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; }
    .ob-chip a:hover { background: #fed7aa; }
    .ob-row { cursor: pointer; }
    .ob-row:hover { background: #fffaf5; }
    .ob-row.is-open { background: #fff7ed; box-shadow: inset 3px 0 0 #f97316; }
    .ob-row.is-sel { background: #eff6ff; }
    .ob-eye { width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: inline-flex; align-items: center; justify-content: center; color: #ea580c; background: #fff7ed; transition: background-color .15s, transform .15s; }
    .ob-eye:hover { background: #ffedd5; transform: scale(1.06); }
    .ob-thumbs { display: flex; margin-top: .3rem; }
    .ob-thumbs img, .ob-thumbs span { width: 1.6rem; height: 1.6rem; border-radius: .45rem; object-fit: cover; border: 2px solid #fff; background: #f3f4f6; margin-left: -.35rem; font-size: .65rem; font-weight: 700; color: #6b7280; display: inline-flex; align-items: center; justify-content: center; }
    .ob-thumbs > :first-child { margin-left: 0; }
    .ob-bulk { position: sticky; top: 0; z-index: 30; }
    .ob-bulk button { font-size: .8rem; font-weight: 600; padding: .35rem .7rem; border-radius: .6rem; background: #fff; border: 1px solid #e5e7eb; }
    .ob-bulk button:hover { border-color: #9ca3af; }
    .ob-loading { opacity: .5; pointer-events: none; }
    .ob-only-m { display: none; }


    .dark .ob-stat, .dark .ob-tab, .dark .oq-card, .dark .ob-bar, .dark .oq-btn, .dark .ob-bulk button { background: #111827; border-color: #1f2937; }
    .dark .ob-stat b, .dark .oq-num, .dark .oq-name, .dark .oq-stats b, .dark .oq-sum dd, .dark .oq-sum .oq-total dt, .dark .oq-sum .oq-total dd { color: #f9fafb; }
    .dark .ob-tab, .dark .oq-btn { color: #d1d5db; }
    .dark .ob-tab.is-on { background: #f97316; border-color: #f97316; }
    .dark .ob-in, .dark .oq-textarea { background: #1f2937; border-color: #374151; color: #f3f4f6; }
    .dark .ob-panel { background: #030712; }
    .dark .oq-stats div, .dark .oq-act, .dark .oq-thumb, .dark .oq-seg { background: #1f2937; border-color: #374151; color: #e5e7eb; }
    .dark .oq-seg button.is-on { background: #374151; }
    .dark .ob-row:hover { background: rgba(249,115,22,.06); }
    .dark .ob-row.is-open { background: rgba(249,115,22,.1); }
    .dark .oq-dot { background: #111827; }

    @media (max-width: 1023px) { .ob-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 767px) {
        .ob-stats { display: flex; overflow-x: auto; gap: .5rem; scrollbar-width: none; margin-left: -1rem; margin-right: -1rem; padding: 0 1rem 2px; }
        .ob-stats::-webkit-scrollbar { display: none; }
        .ob-stats > .ob-stat { flex: 0 0 8.5rem; padding: .65rem .8rem; }
        .ob-only-m { display: block; }
        .ob-filters > * { flex: 1 1 calc(50% - .5rem); min-width: 0; }
        .ob-filters .ob-search { flex-basis: 100%; }
        .ob-table thead { display: none; }
        .ob-table, .ob-table tbody { display: block; }
        .ob-table tr.ob-row { display: grid; grid-template-columns: 1.5rem 1fr auto; grid-template-areas: "chk ord total" ". cust cust" ". pay status" ". date act"; gap: .45rem .6rem; padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9; }
        .ob-table tr.ob-row > td { display: block; padding: 0 !important; text-align: left !important; }
        .ob-c-chk { grid-area: chk; } .ob-c-ord { grid-area: ord; } .ob-c-cust { grid-area: cust; }
        .ob-c-total { grid-area: total; text-align: right !important; } .ob-c-pay { grid-area: pay; } .ob-c-status { grid-area: status; justify-self: end; }
        .ob-c-date { grid-area: date; align-self: center; } .ob-c-act { grid-area: act; }
        .ob-c-pay > div, .ob-c-act > div { justify-content: flex-start !important; }
        .ob-c-act > div { justify-content: flex-end !important; }
        .ob-hide-m { display: none !important; }
        .ob-ov { align-items: flex-end; }
        .ob-panel { width: 100%; height: 94vh; border-radius: 1.25rem 1.25rem 0 0; }
    }
</style>

<script>
    // Orders list: quick view drawer (eye icon / tap a row — see _quickview), bulk status.
    window.orderBoard = function (cfg) {
        return withOrderDrawer(cfg, {
            selected: [], busy: false,
            rowClick(e, id) {
                // links, buttons, checkboxes and the inline status dropdowns inside the row keep their own job
                const hit = e.target.closest('a, button, input, select, label, [x-data]');
                if (hit && e.currentTarget.contains(hit)) return;
                this.show(id);
            },
            toggle(id) { this.selected = this.selected.includes(id) ? this.selected.filter(i => i !== id) : [...this.selected, id]; },
            async bulk(status) {
                if (!this.selected.length || this.busy) return;
                if (['cancelled', 'refunded'].includes(status) && !(await showConfirmModal('Mark ' + this.selected.length + ' order(s) as ' + status + '? Stock is put back for them.'))) return;
                this.busy = true;
                try {
                    const r = await fetch(cfg.bulkUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ ids: this.selected, status }) });
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(d.message || 'Bulk update failed.');
                    this.selected.forEach(id => window.dispatchEvent(new CustomEvent('order-updated', { detail: { id, status } })));
                    this.selected = [];
                    ordersListToast(true, d.message);
                } catch (e) { ordersListToast(false, e.message); } finally { this.busy = false; }
            },
        });
    };
</script>
@include('admin.orders._quickview', ['part' => 'assets'])

<div x-data="orderBoard(@js(['ids' => $orders->pluck('id'), 'quickUrl' => route('admin.orders.quick', '__ID__'), 'bulkUrl' => route('admin.orders.bulk-status')]))"
     @keydown.window="key($event)" @keydown.escape.window="close()" @oq-open="show($event.detail)">

<div class="flex flex-wrap items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Orders</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ number_format($orders->total()) }} order{{ $orders->total() === 1 ? '' : 's' }} found · tap 👁 or a row for the quick view</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @if($flaggedCount > 0)
        <a href="{{ $link(['fraud' => request('fraud') ? null : 1]) }}"
           class="flex items-center gap-2 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-400 px-3.5 py-2 rounded-xl text-sm font-medium hover:bg-red-100 dark:hover:bg-red-500/20 transition {{ request('fraud') ? 'ring-2 ring-red-400 dark:ring-red-600' : '' }}">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Fraud Flagged <span class="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold">{{ $flaggedCount }}</span>
        </a>
        @endif
        <a href="{{ route('admin.orders.create') }}" class="group relative overflow-hidden flex items-center gap-1.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-shadow">
            <span class="pointer-events-none absolute inset-y-0 left-0 w-1/3 -skew-x-12 bg-white/25 -translate-x-[150%] group-hover:translate-x-[350%] transition-transform duration-700 ease-out"></span>
            <svg class="relative w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="relative">Create Order</span>
        </a>
    </div>
</div>

{{-- Today at a glance --}}
<div class="ob-stats mb-4">
    <a href="{{ $link(['range' => 'today'], ['from', 'to', 'status']) }}" class="ob-stat"><span>Today's orders</span><b>{{ $summary['today_orders'] }}</b></a>
    <a href="{{ $link(['range' => 'today'], ['from', 'to', 'status']) }}" class="ob-stat"><span>Today's sales</span><b>৳{{ number_format($summary['today_sales']) }}</b></a>
    <a href="{{ $link(['status' => 'pending'], ['range', 'from', 'to']) }}" class="ob-stat"><span>To confirm</span><b style="color:#d97706">{{ $summary['pending'] }}</b></a>
    <a href="{{ $link(['status' => 'processing'], ['range', 'from', 'to']) }}" class="ob-stat"><span>To ship</span><b style="color:#2563eb">{{ $summary['to_ship'] }}</b></a>
    <a href="{{ route('admin.payments.index') }}" class="ob-stat"><span>Payments to verify</span><b style="color:{{ $summary['unverified'] ? '#ea580c' : '#111827' }}">{{ $summary['unverified'] }}</b></a>
</div>

{{-- Status tabs with live counts --}}
<div class="ob-tabs mb-3">
    @foreach($tabs as $key => $label)
        @php $count = $key === '' ? $allCount : (int) ($statusCounts[$key] ?? 0); @endphp
        <a href="{{ $key === '' ? $link([], ['status']) : $link(['status' => $key]) }}" class="ob-tab {{ (string) request('status') === $key ? 'is-on' : '' }}">
            @if($key !== '')<span style="width:.5rem;height:.5rem;border-radius:999px;background:{{ $tabColor[$key] }}"></span>@endif
            {{ $label }} <i>{{ number_format($count) }}</i>
        </a>
    @endforeach
</div>

{{-- Filters — apply as soon as one changes; remembered until "Clear" --}}
<form action="{{ route('admin.orders.index') }}" method="GET" x-data="{ range: @js($range) }"
      @change="if ($event.target.name !== 'search' && !($event.target.name === 'range' && $event.target.value === 'custom')) $el.requestSubmit()"
      class="ob-filters flex items-center flex-wrap gap-2 mb-3 bg-white dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-800 rounded-2xl shadow-sm p-3">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    @if(request('fraud'))<input type="hidden" name="fraud" value="1">@endif
    <div class="relative flex-1 ob-search" style="min-width: 14rem;">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Order no, name, phone or email…" enterkeyhint="search"
               class="ob-in w-full" style="padding-left: 2.25rem;">
    </div>
    <select name="range" x-model="range" class="ob-in{{ $range ? ' is-set' : '' }}" aria-label="Date">
        @foreach($ranges as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
    </select>
    <template x-if="range === 'custom'">
        <div class="flex items-center gap-2" style="flex-basis: 100%;">
            <input type="date" name="from" value="{{ request('from') }}" class="ob-in" style="flex:1" aria-label="From">
            <span class="text-gray-400">→</span>
            <input type="date" name="to" value="{{ request('to') }}" class="ob-in" style="flex:1" aria-label="To">
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium">Apply</button>
        </div>
    </template>
    <select name="payment_status" class="ob-in{{ request('payment_status') ? ' is-set' : '' }}" aria-label="Payment status">
        <option value="">All payments</option>
        @foreach(['pending', 'paid', 'failed', 'refunded'] as $ps)
            <option value="{{ $ps }}" {{ request('payment_status') === $ps ? 'selected' : '' }}>{{ ucfirst($ps) }}</option>
        @endforeach
    </select>
    @if($paymentMethods->count() > 1)
    <select name="payment_method" class="ob-in{{ request('payment_method') ? ' is-set' : '' }}" aria-label="Payment method">
        <option value="">All methods</option>
        @foreach($paymentMethods as $pm)
            <option value="{{ $pm }}" {{ request('payment_method') === $pm ? 'selected' : '' }}>{{ \App\Services\Notifications\OrderEmail::paymentLabel($pm) }}</option>
        @endforeach
    </select>
    @endif
    <select name="sort" class="ob-in" aria-label="Sort">
        @foreach(['latest' => 'Newest first', 'oldest' => 'Oldest first', 'total_desc' => 'Highest total', 'total_asc' => 'Lowest total'] as $k => $l)
            <option value="{{ $k }}" {{ request('sort', 'latest') === $k ? 'selected' : '' }}>{{ $l }}</option>
        @endforeach
    </select>
    <select name="per_page" class="ob-in ob-hide-m" aria-label="Per page">
        @foreach([15, 30, 50, 100] as $n)<option value="{{ $n }}" {{ (int) request('per_page', 15) === $n ? 'selected' : '' }}>{{ $n }} / page</option>@endforeach
    </select>
    <button type="submit" class="bg-gray-800 dark:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-900 transition">Search</button>
    @if($chips || request('status'))
        <a href="{{ route('admin.orders.index', ['reset' => 1]) }}" class="text-sm text-gray-500 hover:text-gray-700 px-2">Clear all</a>
    @endif
</form>

@if($chips)
<div class="flex flex-wrap items-center gap-1.5 mb-3">
    @foreach($chips as $key => $label)
        <span class="ob-chip">{{ $label }}<a href="{{ $link([], $key === 'range' ? ['range', 'from', 'to'] : [$key]) }}" aria-label="Remove {{ $label }}">✕</a></span>
    @endforeach
</div>
@endif

{{-- Bulk bar --}}
<div x-show="selected.length" x-cloak class="ob-bulk mb-3 bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-900 rounded-2xl px-4 py-3 flex flex-wrap items-center gap-2" :class="busy && 'ob-loading'">
    <span class="text-sm font-bold text-blue-800 dark:text-blue-300" x-text="selected.length + ' selected'"></span>
    <span class="text-xs text-gray-500 mr-1">Mark as:</span>
    <button type="button" @click="bulk('processing')" style="color:#2563eb">✓ Confirmed</button>
    <button type="button" @click="bulk('shipped')" style="color:#7c3aed">🚚 Shipped</button>
    <button type="button" @click="bulk('delivered')" style="color:#059669">🎉 Delivered</button>
    <button type="button" @click="bulk('cancelled')" style="color:#dc2626">✖ Cancelled</button>
    <button type="button" @click="selected = []" class="ml-auto" style="border:0;background:transparent;color:#6b7280">Clear</button>
</div>

<div class="bg-white dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-800 rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full ob-table">
        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
            <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <th class="pl-5 pr-2 py-3 text-left w-8">
                    <input type="checkbox" class="rounded" aria-label="Select all on this page"
                           :checked="ids.length && ids.every(i => selected.includes(i))"
                           @change="selected = $event.target.checked ? [...new Set([...selected, ...ids])] : selected.filter(i => !ids.includes(i))">
                </th>
                <th class="px-4 py-3 text-left">Order</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3 text-center">Payment</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-center">Date</th>
                <th class="px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
            @forelse($orders as $order)
                @php
                    $custName = $order->shipping_name ?: ($order->user->name ?? 'Guest');
                    $avatarPalette = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500', 'bg-cyan-500'];
                    $avatarColor = $avatarPalette[crc32($custName) % count($avatarPalette)];
                    $created = $order->created_at->timezone($tz);
                @endphp
                <tr class="ob-row transition" data-id="{{ $order->id }}" @click="rowClick($event, +$el.dataset.id)"
                    :class="{ 'is-open': current === +$el.dataset.id, 'is-sel': selected.includes(+$el.dataset.id) }">
                    <td class="ob-c-chk pl-5 pr-2 py-4">
                        <input type="checkbox" class="rounded" aria-label="Select {{ $order->order_number }}"
                               :checked="selected.includes(+$el.closest('tr').dataset.id)" @change="toggle(+$el.closest('tr').dataset.id)">
                    </td>
                    <td class="ob-c-ord px-4 py-4">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-orange-600 dark:text-orange-400 text-sm hover:text-orange-700">{{ $order->order_number }}</a>
                            @if($order->is_preorder)<span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full text-white" style="background:#7c3aed;" title="Pre-order — advance ৳{{ number_format($order->advance_amount) }} paid, collect the rest on delivery">PRE-ORDER</span>@endif
                            @if($order->source === 'phone')
                                <span title="Manually entered — phone/offline order" class="text-[10px] font-semibold bg-purple-50 dark:bg-purple-500/15 text-purple-600 dark:text-purple-400 px-1.5 py-0.5 rounded-full">📞 Phone</span>
                            @endif
                            @if($order->is_fraud_flagged)
                                <span title="Fraud Flagged — Score: {{ $order->fraud_score }}" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" style="background:#fee2e2;color:#b91c1c">⚠ Risk</span>
                            @elseif($order->fraud_checked_at && $order->fraud_score >= 20)
                                <span class="text-xs text-yellow-500 font-medium" title="Medium fraud risk — Score: {{ $order->fraud_score }}">⚠</span>
                            @endif
                            @if($order->admin_note)<span title="Note: {{ $order->admin_note }}" class="text-xs">📝</span>@endif
                        </div>
                        <div class="ob-thumbs">
                            @foreach($order->items->take(3) as $it)
                                @if($it->product?->image)<img src="{{ image_thumb($it->product->image, 96) }}" alt="" loading="lazy" title="{{ $it->product_name }} × {{ $it->quantity }}">@else<span title="{{ $it->product_name }}">{{ mb_substr($it->product_name, 0, 1) }}</span>@endif
                            @endforeach
                            @if($order->items_count > 3)<span>+{{ $order->items_count - 3 }}</span>@endif
                        </div>
                    </td>
                    <td class="ob-c-cust px-4 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0 {{ $avatarColor }}">{{ mb_strtoupper(mb_substr($custName, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" style="max-width: 12rem;">{{ $custName }}</p>
                                @if($order->shipping_phone)<p class="text-xs text-gray-500">{{ $order->shipping_phone }}</p>@endif
                            </div>
                        </div>
                    </td>
                    <td class="ob-c-total px-4 py-4 text-right">
                        <p class="font-bold text-gray-900 dark:text-gray-100 text-sm">৳{{ number_format($order->total) }}</p>
                        <p class="text-[11px] text-gray-400">{{ $order->items_count }} item{{ $order->items_count === 1 ? '' : 's' }} · {{ \App\Services\Notifications\OrderEmail::paymentLabel($order->payment_method) }}</p>
                    </td>
                    <td class="ob-c-pay px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <div x-data="inlineStatusDropdown({ url: $el.dataset.url, method: 'PUT', field: 'payment_status', current: $el.dataset.current, options: PAYMENT_OPTIONS, badgeMap: PAYMENT_BADGE_CLASSES })"
                                 data-url="{{ route('admin.orders.update', $order->id) }}" data-current="{{ $order->payment_status }}"
                                 @order-updated.window="if ($event.detail.id === +$el.closest('tr').dataset.id && $event.detail.payment_status) current = $event.detail.payment_status">
                                <button type="button" @click="toggle($event)" :disabled="saving"
                                        class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-medium capitalize transition disabled:opacity-60"
                                        :class="badgeMap[current]">
                                    <span x-text="options[current]"></span>
                                    <svg class="w-3 h-3 opacity-60 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak @click.outside="close()" :style="panelStyle"
                                         class="fixed z-[200] w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 py-1.5 text-sm"
                                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                        <template x-for="(label, key) in (open ? options : {})" :key="key">
                                            <button type="button" @click="select(key)"
                                                    class="w-full flex items-center justify-between px-3.5 py-2 text-left capitalize hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
                                                    :class="key === current ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300'">
                                                <span x-text="label"></span>
                                                <svg x-show="key === current" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            @if($order->payment && $order->payment->status === 'pending_verification')
                            <a href="{{ route('admin.payments.show', $order->payment->id) }}"
                               title="Manual verification needed — {{ $order->payment->payment_method_name }}, Txn: {{ $order->payment->transaction_id ?? 'N/A' }}"
                               class="flex-shrink-0 inline-flex items-center gap-1 bg-orange-50 dark:bg-orange-500/15 text-orange-600 dark:text-orange-400 text-[11px] font-semibold px-2 py-1 rounded-full hover:bg-orange-100 transition">
                                Verify
                            </a>
                            @endif
                        </div>
                    </td>
                    <td class="ob-c-status px-4 py-4 text-center">
                        <div x-data="inlineStatusDropdown({ url: $el.dataset.url, method: 'PATCH', field: 'status', current: $el.dataset.current, options: STATUS_OPTIONS, badgeMap: STATUS_BADGE_CLASSES })"
                             data-url="{{ route('admin.orders.status', $order->id) }}" data-current="{{ $order->status }}"
                             @order-updated.window="if ($event.detail.id === +$el.closest('tr').dataset.id && $event.detail.status) current = $event.detail.status" class="inline-block">
                            <button type="button" @click="toggle($event)" :disabled="saving"
                                    class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-medium capitalize transition disabled:opacity-60"
                                    :class="badgeMap[current]">
                                <span x-text="options[current]"></span>
                                <svg class="w-3 h-3 opacity-60 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak @click.outside="close()" :style="panelStyle"
                                     class="fixed z-[200] w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 py-1.5 text-sm"
                                     x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                    <template x-for="(label, key) in (open ? options : {})" :key="key">
                                        <button type="button" @click="select(key)"
                                                class="w-full flex items-center justify-between px-3.5 py-2 text-left capitalize hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
                                                :class="key === current ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300'">
                                            <span x-text="label"></span>
                                            <svg x-show="key === current" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </td>
                    <td class="ob-c-date px-4 py-4 text-center">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $created->isToday() ? 'Today' : ($created->isYesterday() ? 'Yesterday' : $created->format('M d, Y')) }}</p>
                        <p class="text-[11px] text-gray-400">{{ $created->format('g:i A') }}</p>
                    </td>
                    <td class="ob-c-act px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="ob-eye" @click="show(+$el.closest('tr').dataset.id)" title="Quick view — customer & order details" aria-label="Quick view {{ $order->order_number }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="ob-hide-m w-8 h-8 rounded-lg inline-flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" title="Full details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            <a href="{{ route('admin.orders.invoice', $order->id) }}" data-turbo="false" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" title="Download Invoice PDF">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-16 text-center">
                    <svg class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">No orders match these filters. <a href="{{ route('admin.orders.index', ['reset' => 1]) }}" class="text-orange-600">Clear filters</a></p>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 sm:px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-3">
        <span class="text-sm text-gray-500">{{ number_format($orders->total()) }} order(s) · page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
        {{ $orders->links() }}
    </div>
</div>

@include('admin.orders._quickview', ['part' => 'drawer'])
</div>
@push('scripts')
<script>
    // Custom dropdown (not a native <select>) for the inline Status/Payment editors on
    // the orders list — a native select's own arrow rendered awkwardly cramped against
    // the colored pill, and its options menu can't be styled at all. Submits via fetch
    // to the SAME admin.orders.status / admin.orders.update routes the detail page's own
    // forms use, then recolors the pill in place. These exact class strings already exist
    // literally in Order::getStatusBadgeAttribute()/getPaymentStatusBadgeAttribute(), so
    // Tailwind has already compiled them — nothing here is an unsafelisted dynamic class.
    window.STATUS_BADGE_CLASSES = {
        pending:    'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-400',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-400',
        shipped:    'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-400',
        delivered:  'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-400',
        cancelled:  'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-400',
        refunded:   'bg-gray-100 text-gray-800 dark:bg-gray-500/15 dark:text-gray-400',
    };
    window.STATUS_OPTIONS = { pending: 'Pending', processing: 'Processing', shipped: 'Shipped', delivered: 'Delivered', cancelled: 'Cancelled', refunded: 'Refunded' };
    window.PAYMENT_OPTIONS = { pending: 'Pending', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' };
    window.PAYMENT_BADGE_CLASSES = {
        pending:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-400',
        paid:     'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-400',
        failed:   'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-400',
        refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-500/15 dark:text-gray-400',
    };

    // The panel is x-teleport'd to <body> (escaping the table's overflow-x-auto, which
    // would otherwise clip it exactly like the admin sidebar's flyout menus did) so its
    // position has to be computed from the trigger button's own screen position on open.
    function inlineStatusDropdown({ url, method, field, current, options, badgeMap }) {
        return {
            open: false,
            saving: false,
            current,
            options,
            badgeMap,
            panelTop: 0,
            panelLeft: 0,
            toggle(event) {
                if (this.saving) return;
                this.open ? this.close() : this.openMenu(event);
            },
            openMenu(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.panelTop = rect.bottom + 6;
                this.panelLeft = rect.left;
                this.open = true;
            },
            close() { this.open = false; },
            get panelStyle() {
                return `top:${this.panelTop}px; left:${this.panelLeft}px;`;
            },
            async select(key) {
                this.open = false;
                if (key === this.current) return;
                const prev = this.current;
                this.current = key;
                this.saving = true;

                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                body.append('_method', method);
                body.append(field, key);

                try {
                    const res = await fetch(url, { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Request failed');
                    ordersListToast(true, (field === 'status' ? 'Order status' : 'Payment status') + ' updated.');
                } catch (err) {
                    this.current = prev;
                    ordersListToast(false, 'Could not update — please try again.');
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    function ordersListToast(success, message) {
        document.getElementById('orders-list-toast')?.remove();
        const toast = document.createElement('div');
        toast.id = 'orders-list-toast';
        toast.className = `fixed top-20 right-5 z-[100] px-4 py-3 rounded-xl text-sm font-medium shadow-lg text-white ${success ? 'bg-green-600' : 'bg-red-600'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
@endpush
@endsection
