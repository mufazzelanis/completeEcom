@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

@php
    // Each stat's own duo-tone gradient + a matching light/dark badge tint for the little
    // icon square — chosen so all 8 read as clearly distinct at a glance rather than the
    // flat single-color-per-card look this page had before. Literal strings (not built via
    // string interpolation), so Tailwind's content scanner picks every one of them up on
    // its own — no safelist entry needed, same as this file's classes always have been.
    $pendingPct   = $stats['total_orders'] > 0 ? round($stats['pending_orders'] / $stats['total_orders'] * 100) : 0;
    $paidPct      = $stats['total_orders'] > 0 ? round($stats['paid_orders'] / $stats['total_orders'] * 100) : 0;
    $cancelledPct = $stats['total_orders'] > 0 ? round($stats['cancelled_orders'] / $stats['total_orders'] * 100) : 0;
    $stockOutPct  = $stats['total_products'] > 0 ? round($stats['out_of_stock'] / $stats['total_products'] * 100) : 0;

    $cards = [
        [
            'label' => 'Total Orders', 'value' => $stats['total_orders'], 'count' => $stats['total_orders'],
            'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            'gradient' => 'from-blue-500 to-blue-700', 'glow' => 'bg-blue-500',
            'sub' => $stats['today_orders'] . ' new today', 'href' => route('admin.orders.index'),
        ],
        [
            'label' => 'Pending Orders', 'value' => $stats['pending_orders'], 'count' => $stats['pending_orders'],
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'gradient' => 'from-amber-400 to-amber-600', 'glow' => 'bg-amber-500',
            'sub' => $pendingPct . '% of total', 'href' => route('admin.orders.index', ['status' => 'pending']),
            'progress' => $pendingPct,
        ],
        [
            'label' => 'Paid Orders', 'value' => $stats['paid_orders'], 'count' => $stats['paid_orders'],
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'gradient' => 'from-emerald-500 to-emerald-700', 'glow' => 'bg-emerald-500',
            'sub' => '৳' . number_format($stats['total_revenue']) . ' collected', 'href' => route('admin.orders.index', ['payment_status' => 'paid']),
            'progress' => $paidPct,
        ],
        [
            'label' => 'Cancelled / Refunded', 'value' => $stats['cancelled_orders'], 'count' => $stats['cancelled_orders'],
            'icon' => 'M6 18L18 6M6 6l12 12',
            'gradient' => 'from-rose-500 to-rose-700', 'glow' => 'bg-rose-500',
            'sub' => '৳' . number_format($stats['cancelled_revenue']) . ' lost', 'href' => route('admin.orders.index', ['status' => 'cancelled,refunded']),
            'progress' => $cancelledPct,
        ],
        [
            'label' => 'Revenue', 'value' => '৳' . number_format($stats['total_revenue']), 'count' => $stats['total_revenue'], 'prefix' => '৳',
            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'gradient' => 'from-violet-500 to-violet-700', 'glow' => 'bg-violet-500',
            'sub' => 'Avg ৳' . number_format($stats['avg_order_value']) . '/order', 'href' => route('admin.reports.sales'),
        ],
        [
            'label' => 'Total Products', 'value' => $stats['total_products'], 'count' => $stats['total_products'],
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'gradient' => 'from-cyan-500 to-cyan-700', 'glow' => 'bg-cyan-500',
            'sub' => $stats['total_categories'] . ' categories', 'href' => route('admin.products.index'),
        ],
        [
            'label' => 'Out of Stock', 'value' => $stats['out_of_stock'], 'count' => $stats['out_of_stock'],
            'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4',
            'gradient' => 'from-orange-500 to-red-600', 'glow' => 'bg-red-500',
            'sub' => 'need restock', 'href' => route('admin.products.index', ['stock_status' => 'out']),
            'progress' => $stockOutPct,
        ],
        [
            'label' => 'Customers', 'value' => $stats['total_users'], 'count' => $stats['total_users'],
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'gradient' => 'from-fuchsia-500 to-fuchsia-700', 'glow' => 'bg-fuchsia-500',
            'sub' => $stats['new_customers'] . ' new this month', 'href' => route('admin.users.index', ['role' => 'customer']),
        ],
    ];
@endphp

<style>
    @keyframes dashIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .dash-in { animation: dashIn .5s cubic-bezier(.22,1,.36,1) both; }
    @media (prefers-reduced-motion: reduce) { .dash-in { animation: none !important; } }
</style>

{{-- Greeting --}}
<div class="dash-in flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
            Welcome back, {{ explode(' ', auth()->user()->name)[0] }} <span class="inline-block">👋</span>
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ now()->format('l, d F Y') }} — here's how your store is doing.</p>
    </div>
</div>

{{-- Live visitors right now — polls Admin → Live Visitors' summary every 10 s (paused while the tab is hidden) --}}
<a href="{{ route('admin.live-visitors.index') }}"
   class="dash-in relative block overflow-hidden rounded-2xl p-4 sm:p-5 mb-6 text-white shadow-lg hover:shadow-xl transition"
   style="background: linear-gradient(120deg, #4338ca 0%, #7c3aed 50%, #db2777 100%);"
   x-data="{
        count: null, checkout: 0, byType: {}, top: [], timer: null,
        labels: { checkout: ['Checkout','💳'], cart: ['Cart','🛒'], product: ['Product','🧴'], search: ['Search','🔎'], category: ['Category','🗂️'], brand: ['Brand','🏷️'], shop: ['Shop','🛍️'], home: ['Home','🏠'], landing: ['Landing','🚀'], blog: ['Blog','📝'], account: ['Account','👤'], other: ['Other','📄'] },
        async load() {
            if (document.hidden) return;
            try {
                const r = await fetch('{{ route('admin.live-visitors.summary') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) return;
                const d = await r.json();
                this.count = d.count; this.checkout = d.checkout; this.byType = d.byType; this.top = d.top;
            } catch (e) {}
        },
        init() {
            this.load();
            this.timer = setInterval(() => this.load(), 10000);
            const stop = () => { clearInterval(this.timer); document.removeEventListener('turbo:before-visit', stop); };
            document.addEventListener('turbo:before-visit', stop);
        },
        destroy() { clearInterval(this.timer); }
   }">
    <div class="pointer-events-none absolute -top-12 -right-8 w-48 h-48 rounded-full" style="background: rgba(255,255,255,.12);"></div>
    <div class="relative flex flex-wrap items-center gap-x-6 gap-y-3">
        <div class="flex items-center gap-3">
            <span class="relative flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full" style="background:#4ade80; opacity:.75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5" style="background:#22c55e"></span>
            </span>
            <div>
                <p class="text-3xl font-extrabold leading-none tabular-nums" x-text="count === null ? '…' : count"></p>
                <p class="text-xs uppercase tracking-wider" style="color: rgba(255,255,255,.8);">live now</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-1.5 flex-1 min-w-0">
            <template x-for="(n, type) in byType" :key="type">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background: rgba(255,255,255,.18);"
                      x-text="(labels[type] || labels.other)[1] + ' ' + (labels[type] || labels.other)[0] + ' ' + n"></span>
            </template>
            <span x-show="count === 0" class="text-sm" style="color: rgba(255,255,255,.8);">Nobody on the site right now</span>
        </div>
        <div class="hidden md:flex items-center gap-2">
            <template x-for="p in top" :key="p.url">
                <span class="flex items-center gap-2 rounded-xl pl-1 pr-2.5 py-1" style="background: rgba(255,255,255,.16);" :title="p.name">
                    <img :src="p.image || ''" x-show="p.image" alt="" class="w-8 h-8 rounded-lg object-contain bg-white">
                    <span class="text-xs leading-tight">
                        <span class="block truncate font-semibold" style="max-width: 9rem;" x-text="p.name"></span>
                        <span style="color: rgba(255,255,255,.85);" x-text="'৳' + Math.round(p.price).toLocaleString() + ' · 👀 ' + p.viewers"></span>
                    </span>
                </span>
            </template>
        </div>
        <span class="text-sm font-semibold whitespace-nowrap" style="color: rgba(255,255,255,.9);">
            <span x-show="checkout > 0" class="mr-2 rounded-full px-2 py-0.5" style="background:#10b981;" x-text="'💳 ' + checkout + ' at checkout'"></span>
            View all →
        </span>
    </div>
</a>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-8">
    @foreach($cards as $i => $stat)
        <a href="{{ $stat['href'] }}"
           class="dash-in group relative block bg-white dark:bg-gray-900 rounded-2xl shadow-sm dark:shadow-none ring-1 ring-gray-100 dark:ring-gray-800 p-5 sm:p-6 overflow-hidden hover:shadow-xl hover:-translate-y-1 hover:ring-gray-200 dark:hover:ring-gray-700 transition-all duration-300"
           style="animation-delay: {{ $i * 70 }}ms">
            {{-- Soft color glow tucked in the corner — same hue as the icon, so the whole
                 card reads as "belonging" to that color instead of the icon floating alone
                 on a plain white/gray surface. --}}
            <div class="pointer-events-none absolute -right-8 -top-8 w-28 h-28 rounded-full {{ $stat['glow'] }} opacity-[0.08] dark:opacity-[0.15] blur-2xl group-hover:opacity-[0.16] dark:group-hover:opacity-[0.25] group-hover:scale-125 transition-all duration-500"></div>

            <div class="relative flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br {{ $stat['gradient'] }} shadow-md shadow-gray-900/10 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/>
                    </svg>
                </div>
                <svg class="w-4 h-4 text-gray-300 dark:text-gray-700 group-hover:text-gray-400 dark:group-hover:text-gray-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M7 7h10v10"/></svg>
            </div>

            @if(is_numeric($stat['count']))
                <p class="relative text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tabular-nums"
                   data-count-to="{{ $stat['count'] }}" data-count-prefix="{{ $stat['prefix'] ?? '' }}">{{ $stat['prefix'] ?? '' }}0</p>
            @else
                <p class="relative text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tabular-nums">{{ $stat['value'] }}</p>
            @endif
            <p class="relative text-sm font-semibold text-gray-700 dark:text-gray-300 mt-1">{{ $stat['label'] }}</p>
            <p class="relative text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $stat['sub'] }}</p>

            @if(isset($stat['progress']))
                <div class="relative mt-3 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r {{ $stat['gradient'] }} transition-all duration-700 ease-out" style="width: 0%" data-progress-to="{{ min(100, $stat['progress']) }}"></div>
                </div>
            @endif
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Orders — live: tabs, one-tap next step, quick view, refreshes every 45s -->
    <style>
        .do-tabs { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
        .do-tabs::-webkit-scrollbar { display: none; }
        .do-tab { flex-shrink: 0; display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 600; color: #4b5563; background: #f8fafc; border: 1px solid #eef2f7; white-space: nowrap; }
        .do-tab:hover { border-color: #fdba74; }
        .do-tab.is-on { background: #111827; border-color: #111827; color: #fff; }
        .do-tab i { font-style: normal; font-size: .7rem; font-weight: 800; padding: 0 .4rem; border-radius: 999px; background: #fff; color: #111827; min-width: 1.2rem; text-align: center; }
        .do-tab i.is-hot { background: #f97316; color: #fff; }
        .do-tab.is-on i { background: rgba(255,255,255,.2); color: #fff; }
        .do-list { min-height: 12rem; }
        .do-row { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; align-items: center; gap: .85rem; padding: .8rem 1.25rem; border-bottom: 1px solid #f8fafc; cursor: pointer; transition: background-color .15s; }
        .do-row:last-child { border-bottom: 0; }
        .do-row:hover { background: #fffaf5; }
        .do-row.is-open { background: #fff7ed; box-shadow: inset 3px 0 0 #f97316; }
        .do-row.is-new { animation: doNew 2.5s ease-out; }
        @keyframes doNew { 0%, 40% { background: #ffedd5; } 100% { background: transparent; } }
        .do-av { width: 2.4rem; height: 2.4rem; border-radius: 999px; color: #fff; font-weight: 800; font-size: .85rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .do-main { min-width: 0; }
        .do-name { display: flex; align-items: center; gap: .35rem; font-size: .9rem; font-weight: 700; color: #111827; min-width: 0; }
        .do-tag { flex-shrink: 0; font-size: .6rem; font-weight: 800; padding: .08rem .4rem; border-radius: 999px; letter-spacing: .03em; }
        .do-meta { font-size: .75rem; color: #9ca3af; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .do-num { color: #ea580c; font-weight: 700; }
        .do-thumbs { display: flex; margin-top: .35rem; }
        .do-thumbs img, .do-thumbs span { width: 1.5rem; height: 1.5rem; border-radius: .4rem; object-fit: cover; border: 2px solid #fff; background: #f3f4f6; margin-left: -.3rem; font-size: .6rem; font-weight: 700; color: #6b7280; display: inline-flex; align-items: center; justify-content: center; }
        .do-thumbs > :first-child { margin-left: 0; }
        .do-side { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: .2rem; }
        .do-total { font-size: .95rem; font-weight: 800; color: #111827; }
        .do-pay { font-size: .7rem; color: #6b7280; display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
        .do-dot { width: .45rem; height: .45rem; border-radius: 999px; }
        .do-acts { display: flex; align-items: center; gap: .4rem; }
        .do-go { font-size: .75rem; font-weight: 700; padding: .4rem .7rem; border-radius: .65rem; background: #059669; color: #fff; white-space: nowrap; transition: background-color .15s; }
        .do-go:hover { background: #047857; }
        .do-go:disabled { opacity: .5; }
        .do-eye { width: 2rem; height: 2rem; border-radius: .65rem; display: inline-flex; align-items: center; justify-content: center; color: #ea580c; background: #fff7ed; }
        .do-eye:hover { background: #ffedd5; }
        .do-empty { text-align: center; padding: 2.5rem 1rem; }
        .do-live { display: inline-flex; align-items: center; gap: .35rem; font-size: .7rem; font-weight: 700; color: #059669; background: #ecfdf5; padding: .15rem .55rem; border-radius: 999px; }
        .do-live b { width: .45rem; height: .45rem; border-radius: 999px; background: #10b981; animation: doPulse 1.6s infinite; }
        @keyframes doPulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,.5); } 70% { box-shadow: 0 0 0 6px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
        .dark .do-tab { background: #1f2937; border-color: #374151; color: #d1d5db; }
        .dark .do-tab.is-on { background: #f97316; border-color: #f97316; }
        .dark .do-tab i { background: #111827; color: #e5e7eb; }
        .dark .do-tabs, .dark .do-row { border-color: #1f2937; }
        .dark .do-row:hover { background: rgba(249,115,22,.06); }
        .dark .do-row.is-open { background: rgba(249,115,22,.1); }
        .dark .do-name, .dark .do-total { color: #f9fafb; }
        .dark .do-thumbs img, .dark .do-thumbs span { border-color: #111827; background: #1f2937; }
        @media (max-width: 640px) {
            .do-row { grid-template-columns: auto minmax(0, 1fr) auto; grid-template-areas: "av main side" "av acts acts"; padding: .8rem 1rem; gap: .5rem .7rem; align-items: start; }
            .do-av { grid-area: av; width: 2.1rem; height: 2.1rem; }
            .do-main { grid-area: main; } .do-side { grid-area: side; } .do-acts { grid-area: acts; justify-content: flex-end; }
            .do-hide-s { display: none; }
            .do-tabs { padding: .65rem 1rem; }
        }
    </style>
    <script>
        window.dashOrders = function (cfg) {
            return withOrderDrawer(cfg, {
                tab: 'all', counts: cfg.counts, latest: cfg.latest, busy: null, refreshing: false, timer: null,
                init() {
                    const m = location.hash.match(/^#order-(\d+)$/);
                    if (m) this.show(+m[1]);
                    this.timer = setInterval(() => { if (document.visibilityState === 'visible' && !this.open) this.load(true); }, 45000);
                    this._onUpdate = () => this.load();
                    window.addEventListener('order-updated', this._onUpdate);
                },
                destroy() { clearInterval(this.timer); window.removeEventListener('order-updated', this._onUpdate); },
                async load(poll = false) {
                    if (this.refreshing) return;
                    this.refreshing = true;
                    try {
                        const since = poll ? this.latest : 0;
                        const r = await fetch(cfg.url + '?tab=' + this.tab + '&since=' + since, { headers: { Accept: 'application/json' } });
                        if (!r.ok) return;
                        const d = await r.json();
                        if (poll && d.latest > this.latest) {
                            const n = d.latest - this.latest;
                            ordersListToast(true, '🔔 ' + n + ' new order' + (n > 1 ? 's' : '') + ' just came in');
                        }
                        this.$refs.list.innerHTML = d.html;
                        this.ids = d.ids; this.counts = d.counts; this.latest = Math.max(this.latest, d.latest);
                    } finally { this.refreshing = false; }
                },
                pick(t) { if (t === this.tab) return; this.tab = t; this.load(); },
                rowClick(e, id) {
                    const hit = e.target.closest('a, button, input, select, label');
                    if (hit && e.currentTarget.contains(hit)) return;
                    this.show(id);
                },
                async advance(id, status) {
                    this.busy = id;
                    try {
                        const r = await fetch(cfg.statusUrl.replace('__ID__', id), { method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ status }) });
                        if (!r.ok) throw new Error();
                        ordersListToast(true, '✓ Order moved to ' + ({ processing: 'Confirmed', shipped: 'Shipped', delivered: 'Delivered' })[status]);
                        await this.load();
                    } catch (e) { ordersListToast(false, 'Could not update — please try again.'); } finally { this.busy = null; }
                },
            });
        };
    </script>
    @include('admin.orders._quickview', ['part' => 'assets'])

    <div class="lg:col-span-2 min-w-0"
         x-data="dashOrders(@js([
            'ids' => $recentOrders->pluck('id'),
            'counts' => $orderTabCounts,
            'latest' => (int) \App\Models\Order::max('id'),
            'url' => route('admin.dashboard.orders'),
            'quickUrl' => route('admin.orders.quick', '__ID__'),
            'statusUrl' => route('admin.orders.status', '__ID__'),
         ]))" @keydown.window="key($event)" @keydown.escape.window="close()" @oq-open="show($event.detail)">
    <div class="dash-in bg-white dark:bg-gray-900 rounded-2xl shadow-sm dark:shadow-none ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden" style="animation-delay: 220ms">
        <div class="flex items-center justify-between gap-3 px-5 pt-5 pb-1">
            <div class="flex items-center gap-2.5">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Orders</h2>
                <span class="do-live" title="Updates by itself every 45 seconds"><b></b>Live</span>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-orange-600 dark:text-orange-400 text-sm font-medium hover:text-orange-700 transition">View all →</a>
        </div>
        <div class="do-tabs">
            @foreach(\App\Http\Controllers\Admin\DashboardController::ORDER_TABS as $key => $label)
                <button type="button" class="do-tab" :class="tab === '{{ $key }}' && 'is-on'" @click="pick('{{ $key }}')">
                    {{ $label }}
                    @if($key !== 'all')<i :class="{{ $key === 'pending' ? 'counts.pending > 0' : 'false' }} && 'is-hot'" x-text="counts['{{ $key }}']">{{ $orderTabCounts[$key] }}</i>@endif
                </button>
            @endforeach
        </div>
        <div class="do-list" x-ref="list" :class="refreshing && 'ob-loading'">
            @include('admin.dashboard._orders', ['orders' => $recentOrders, 'newSince' => 0])
        </div>
    </div>
        @include('admin.orders._quickview', ['part' => 'drawer'])
    </div>

    <!-- Top Products -->
    <div class="dash-in bg-white dark:bg-gray-900 rounded-2xl shadow-sm dark:shadow-none ring-1 ring-gray-100 dark:ring-gray-800" style="animation-delay: 280ms">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Top Selling Products</h2>
            <a href="{{ route('admin.products.index') }}" class="text-orange-600 dark:text-orange-400 text-sm font-medium hover:text-orange-700 dark:hover:text-orange-300 transition">View All</a>
        </div>
        <div class="p-4 space-y-1">
            @php
                $rankStyle = [
                    1 => 'bg-gradient-to-br from-amber-400 to-yellow-500 text-white',
                    2 => 'bg-gradient-to-br from-gray-300 to-gray-400 text-white',
                    3 => 'bg-gradient-to-br from-orange-400 to-amber-600 text-white',
                ];
                $maxRevenue = max(1, (float) ($topProducts->max('revenue') ?? 1));
            @endphp
            @forelse($topProducts as $rank => $item)
                <a href="{{ $item->product ? route('admin.products.edit', $item->product_id) : '#' }}"
                   class="relative flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition {{ $item->product ? '' : 'pointer-events-none' }}">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold flex-shrink-0 {{ $rankStyle[$rank + 1] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }}">{{ $rank + 1 }}</span>
                    <div class="w-10 h-10 bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                        @if($item->product?->image)
                            <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $item->qty_sold }} sold ·
                            Price ৳{{ number_format($item->product?->sale_price ?? $item->product?->price ?? 0) }}
                        </p>
                        <div class="mt-1.5 h-1 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-orange-400 to-orange-600" style="width: {{ max(6, round($item->revenue / $maxRevenue * 100)) }}%"></div>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 flex-shrink-0">৳{{ number_format($item->revenue) }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">No paid orders yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Count-up KPI numbers — one-shot, IntersectionObserver-gated so it only plays once
    // each card actually scrolls into view (in practice, immediately: these sit above the
    // fold on every screen size this admin panel supports).
    var counters = document.querySelectorAll('[data-count-to]');
    if (counters.length && 'IntersectionObserver' in window) {
        var countObserver = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                obs.unobserve(entry.target);
                var el = entry.target;
                var end = parseFloat(el.getAttribute('data-count-to')) || 0;
                var prefix = el.getAttribute('data-count-prefix') || '';
                if (reduceMotion || end === 0) { el.textContent = prefix + end.toLocaleString('en-US'); return; }
                var duration = 800, start = null;
                var step = function (ts) {
                    if (start === null) start = ts;
                    var progress = Math.min((ts - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = prefix + Math.round(end * eased).toLocaleString('en-US');
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            });
        }, { threshold: 0.2 });
        counters.forEach(function (el) { countObserver.observe(el); });
    } else {
        counters.forEach(function (el) { el.textContent = (el.getAttribute('data-count-prefix') || '') + (parseFloat(el.getAttribute('data-count-to')) || 0).toLocaleString('en-US'); });
    }

    // Mini progress bars — animate their width in from 0 once, same one-shot pattern.
    var bars = document.querySelectorAll('[data-progress-to]');
    requestAnimationFrame(function () {
        setTimeout(function () {
            bars.forEach(function (bar) { bar.style.width = bar.getAttribute('data-progress-to') + '%'; });
        }, reduceMotion ? 0 : 150);
    });
});
</script>

@endsection
