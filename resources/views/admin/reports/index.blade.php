@extends('layouts.admin')
@section('title', 'Analytics Overview')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Key Metrics --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $metrics = [
        ['Today Revenue', '$'.number_format($todayRevenue,2), 'text-green-600', 'bg-green-50', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Month Revenue', '$'.number_format($monthRevenue,2), 'text-indigo-600', 'bg-indigo-50', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['Total Orders', number_format($totalOrders), 'text-blue-600', 'bg-blue-50', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
        ['Pending Orders', number_format($pendingOrders), 'text-orange-600', 'bg-orange-50', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Total Customers', number_format($totalCustomers), 'text-purple-600', 'bg-purple-50', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['New This Month', number_format($newCustomers), 'text-pink-600', 'bg-pink-50', 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
        ['Active Products', number_format($totalProducts), 'text-teal-600', 'bg-teal-50', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['Out of Stock', number_format($outOfStock), 'text-red-600', 'bg-red-50', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    ];
    @endphp
    @foreach($metrics as [$label, $value, $textColor, $bgColor, $icon])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $label }}</p>
            <div class="w-9 h-9 {{ $bgColor }} rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 {{ $textColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-800">{{ $value }}</p>
        @if($label === 'Month Revenue' && $revenueGrowth !== 0)
        <p class="text-xs mt-1 {{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $revenueGrowth >= 0 ? '↑' : '↓' }} {{ abs($revenueGrowth) }}% vs last month
        </p>
        @endif
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Revenue Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Revenue — Last 30 Days</h3>
            <a href="{{ route('admin.reports.sales') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View detailed →</a>
        </div>
        <canvas id="revenueChart" height="120"></canvas>
    </div>

    {{-- Orders by Status --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by Status</h3>
        <canvas id="statusChart" height="180"></canvas>
        <div class="mt-4 space-y-1">
            @foreach($ordersByStatus as $s)
            <div class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-600">{{ $s->status }}</span>
                <span class="font-medium text-gray-800">{{ number_format($s->count) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    {{-- Top Products --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Top Products This Month</h3>
            <a href="{{ route('admin.reports.products') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all →</a>
        </div>
        <div class="space-y-3">
            @forelse($topProducts as $i => $p)
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $p->product_name }}</p>
                    <p class="text-xs text-gray-400">{{ number_format($p->qty_sold) }} units sold</p>
                </div>
                <span class="text-sm font-semibold text-gray-700 flex-shrink-0">${{ number_format($p->revenue, 2) }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">No sales this month yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Recent Orders</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all →</a>
        </div>
        <div class="space-y-3">
            @foreach($recentOrders as $order)
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-700">${{ number_format($order->total, 2) }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $order->status_badge }}">{{ ucfirst($order->status) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = @json($last30->pluck('date'));
    const revenues = @json($last30->pluck('revenue')->map(fn($v) => round($v, 2)));
    const orders = @json($last30->pluck('orders'));

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Revenue ($)',
                data: revenues,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
            }, {
                label: 'Orders',
                data: orders,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.05)',
                fill: false,
                tension: 0.4,
                pointRadius: 2,
                yAxisID: 'y2',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 10 } } },
                y: { ticks: { callback: v => '$' + v.toLocaleString(), font: { size: 10 } } },
                y2: { position: 'right', grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });

    const statusData = @json($ordersByStatus);
    const statusColors = { pending:'#f59e0b', processing:'#3b82f6', shipped:'#8b5cf6', delivered:'#10b981', cancelled:'#ef4444', refunded:'#6b7280' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
            datasets: [{ data: statusData.map(s => s.count), backgroundColor: statusData.map(s => statusColors[s.status] ?? '#94a3b8'), borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
    });
});
</script>
@endsection
