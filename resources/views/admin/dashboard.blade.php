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
    <!-- Recent Orders -->
    <div class="dash-in lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl shadow-sm dark:shadow-none ring-1 ring-gray-100 dark:ring-gray-800" style="animation-delay: 220ms">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-800">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Recent Orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-orange-600 dark:text-orange-400 text-sm font-medium hover:text-orange-700 dark:hover:text-orange-300 transition">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Customer</th>
                        <th class="px-6 py-3 text-left">Total</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse($recentOrders as $order)
                        @php
                            $custName = $order->user->name ?? 'Guest';
                            $avatarPalette = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500', 'bg-cyan-500'];
                            $avatarColor = $avatarPalette[crc32($custName) % count($avatarPalette)];
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order->id) }}'">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-orange-600 dark:text-orange-400 text-sm hover:text-orange-700 dark:hover:text-orange-300">{{ $order->order_number }}</a>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $order->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0 {{ $avatarColor }}">{{ strtoupper(substr($custName, 0, 1)) }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $custName }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">৳{{ number_format($order->total) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->status_badge }}">{{ $order->status }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->payment_status_badge }}">{{ $order->payment_status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
