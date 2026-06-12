@extends('admin.reports.layout')
@section('report-title', 'Order Report')

@section('report-content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.orders') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <select name="group_by" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="day"   {{ $groupBy === 'day'   ? 'selected' : '' }}>Daily</option>
            <option value="week"  {{ $groupBy === 'week'  ? 'selected' : '' }}>Weekly</option>
            <option value="month" {{ $groupBy === 'month' ? 'selected' : '' }}>Monthly</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <div class="flex gap-2 ml-auto">
            @foreach([['7d','7 Days'],['30d','30 Days'],['90d','90 Days'],['ytd','Year to Date']] as [$preset,$label])
            <a href="{{ route('admin.reports.orders', ['from' => match($preset){ '7d'=>now()->subDays(6)->toDateString(), '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $label }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
    $statuses = [
        ['Total', $summary->total ?? 0, 'text-gray-800', 'bg-gray-50'],
        ['Pending', $summary->pending ?? 0, 'text-orange-700', 'bg-orange-50'],
        ['Processing', $summary->processing ?? 0, 'text-blue-700', 'bg-blue-50'],
        ['Shipped', $summary->shipped ?? 0, 'text-purple-700', 'bg-purple-50'],
        ['Delivered', $summary->delivered ?? 0, 'text-green-700', 'bg-green-50'],
        ['Cancelled', ($summary->cancelled ?? 0) + ($summary->refunded ?? 0), 'text-red-700', 'bg-red-50'],
    ];
    @endphp
    @foreach($statuses as [$label, $value, $tc, $bg])
    <div class="bg-white rounded-2xl shadow-sm p-4 {{ $bg }} border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ number_format($value) }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Order Volume Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Order Volume Trend</h3>
        <canvas id="orderTrendChart" height="120"></canvas>
    </div>

    {{-- Orders by Status Doughnut --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">By Status</h3>
        <canvas id="statusChart" height="180"></canvas>
        <div class="mt-4 space-y-1">
            @foreach($byStatus as $s)
            @php
            $statusColors = ['pending'=>'bg-orange-400','processing'=>'bg-blue-400','shipped'=>'bg-purple-400','delivered'=>'bg-green-400','cancelled'=>'bg-red-400','refunded'=>'bg-gray-400'];
            @endphp
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full {{ $statusColors[$s->status] ?? 'bg-gray-300' }}"></span><span class="capitalize text-gray-600">{{ $s->status }}</span></span>
                <span class="font-medium text-gray-800">{{ number_format($s->count) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- Payment Method --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by Payment Method</h3>
        <canvas id="paymentChart" height="160"></canvas>
        <div class="mt-4 space-y-2">
            @foreach($byPayment as $p)
            <div class="flex items-center justify-between text-sm">
                <span class="capitalize text-gray-600">{{ $p->payment_method }}</span>
                <div class="text-right">
                    <span class="font-medium text-gray-800">{{ number_format($p->count) }} orders</span>
                    <span class="text-gray-400 ml-2">${{ number_format($p->revenue,0) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top Cities --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Top Cities by Orders</h3>
        @php $maxCityOrders = $byCity->max('count') ?: 1; @endphp
        <div class="space-y-3">
            @forelse($byCity as $city)
            @php $pct = round(($city->count / $maxCityOrders) * 100); @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $city->shipping_city }}</span>
                    <span class="font-semibold text-gray-800">{{ number_format($city->count) }} <span class="text-gray-400 font-normal text-xs">${{ number_format($city->revenue,0) }}</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-6">No data for this period.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Avg Order Value --}}
<div class="grid grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-2">Average Order Value</h3>
        <p class="text-3xl font-bold text-indigo-600 mb-1">${{ number_format($summary->avg_order_value ?? 0, 2) }}</p>
        <p class="text-xs text-gray-400">Across all {{ number_format($summary->total ?? 0) }} orders in period</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-2">Total Revenue (Period)</h3>
        <p class="text-3xl font-bold text-green-600 mb-1">${{ number_format($summary->total_revenue ?? 0, 2) }}</p>
        <p class="text-xs text-gray-400">Excluding cancelled & refunded</p>
    </div>
</div>

{{-- Trend Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Order Trend Detail</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">Period</th>
                    <th class="px-6 py-3 text-right">Total</th>
                    <th class="px-6 py-3 text-right">Pending</th>
                    <th class="px-6 py-3 text-right">Processing</th>
                    <th class="px-6 py-3 text-right">Shipped</th>
                    <th class="px-6 py-3 text-right">Delivered</th>
                    <th class="px-6 py-3 text-right">Cancelled</th>
                    <th class="px-6 py-3 text-right">Avg Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orderTrend as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700">{{ $row->period }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($row->total_orders) }}</td>
                    <td class="px-6 py-3 text-right text-orange-600">{{ number_format($row->pending) }}</td>
                    <td class="px-6 py-3 text-right text-blue-600">{{ number_format($row->processing) }}</td>
                    <td class="px-6 py-3 text-right text-purple-600">{{ number_format($row->shipped) }}</td>
                    <td class="px-6 py-3 text-right text-green-600">{{ number_format($row->delivered) }}</td>
                    <td class="px-6 py-3 text-right text-red-500">{{ number_format($row->cancelled + $row->refunded) }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">${{ number_format($row->avg_order_value, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">No order data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pending Orders --}}
@if($recentPending->isNotEmpty())
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">Pending Orders (Latest)</h3>
        <a href="{{ route('admin.orders.index', ['status'=>'pending']) }}" class="text-xs text-indigo-600 hover:text-indigo-800">View all →</a>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($recentPending as $order)
        <div class="px-6 py-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
                <p class="text-xs text-gray-400">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-700">${{ number_format($order->total, 2) }}</p>
                <span class="text-xs text-orange-600 font-medium">{{ ucfirst($order->payment_method) }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($orderTrend);
    new Chart(document.getElementById('orderTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(r => r.period),
            datasets: [
                { label: 'Delivered', data: trend.map(r => r.delivered), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3 },
                { label: 'Shipped',   data: trend.map(r => r.shipped),   backgroundColor: 'rgba(139,92,246,0.65)', borderRadius: 3 },
                { label: 'Processing',data: trend.map(r => r.processing),backgroundColor: 'rgba(59,130,246,0.65)', borderRadius: 3 },
                { label: 'Pending',   data: trend.map(r => r.pending),   backgroundColor: 'rgba(245,158,11,0.65)', borderRadius: 3 },
                { label: 'Cancelled', data: trend.map(r => parseInt(r.cancelled)+parseInt(r.refunded)), backgroundColor: 'rgba(239,68,68,0.55)', borderRadius: 3 },
            ]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { stacked: true, grid: { display:false }, ticks: { font: { size:10 }, maxTicksLimit:15 } }, y: { stacked: true, ticks: { font: { size:10 } } } } }
    });

    const st = @json($byStatus);
    const statusColors = { pending:'#f59e0b', processing:'#3b82f6', shipped:'#8b5cf6', delivered:'#10b981', cancelled:'#ef4444', refunded:'#6b7280' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: st.map(s => s.status.charAt(0).toUpperCase()+s.status.slice(1)),
            datasets: [{ data: st.map(s => s.count), backgroundColor: st.map(s => statusColors[s.status] ?? '#94a3b8'), borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '62%' }
    });

    const pm = @json($byPayment);
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: pm.map(p => p.payment_method.toUpperCase()),
            datasets: [{ data: pm.map(p => p.count), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position:'bottom', labels: { font: { size:11 } } } }, cutout: '60%' }
    });
});
</script>
@endsection
