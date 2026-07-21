@extends('admin.reports.layout')
@section('report-title', 'Returns & Refunds Report')

@section('report-content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.returns') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <div class="flex gap-2 ml-auto">
            @foreach([['30d','30 Days'],['90d','90 Days'],['ytd','This Year']] as [$p,$l])
            <a href="{{ route('admin.reports.returns', ['from' => match($p){ '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['Return Rate', $returnRate.'%', 'text-red-700', 'bg-red-50', 'Of '.(number_format($summary->total_orders ?? 0)).' total orders'],
        ['Returned Orders', number_format($summary->returned_count ?? 0), 'text-orange-700', 'bg-orange-50', 'Cancelled + Refunded'],
        ['Revenue Lost', '৳'.number_format($summary->lost_revenue ?? 0,2), 'text-red-700', 'bg-red-50', 'Total value of returns'],
        ['Refunded', number_format($summary->refunded_count ?? 0).' orders', 'text-gray-700', 'bg-gray-50', '৳'.number_format($summary->refunded_revenue ?? 0,2).' refunded'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg,$sub])
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $sub }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Return Trend --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Return Trend — Last 12 Months</h3>
        <canvas id="returnTrendChart" height="130"></canvas>
    </div>

    {{-- Cancelled vs Refunded --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Cancelled vs Refunded</h3>
        <canvas id="returnTypePieChart" height="160"></canvas>
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-orange-800">Cancelled</p>
                    <p class="text-xs text-orange-600">{{ number_format($summary->cancelled_count ?? 0) }} orders</p>
                </div>
                <p class="font-bold text-orange-700">-৳{{ number_format($summary->cancelled_revenue ?? 0,0) }}</p>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-gray-700">Refunded</p>
                    <p class="text-xs text-gray-500">{{ number_format($summary->refunded_count ?? 0) }} orders</p>
                </div>
                <p class="font-bold text-gray-700">-৳{{ number_format($summary->refunded_revenue ?? 0,0) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- Returns by Payment Method --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Returns by Payment Method</h3>
        @php $maxRet = $returnsByPayment->max('count') ?: 1; @endphp
        <div class="space-y-3">
            @forelse($returnsByPayment as $pm)
            @php $pct = round(($pm->count / $maxRet) * 100); @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="uppercase text-gray-700 font-medium">{{ $pm->payment_method }}</span>
                    <span class="font-semibold text-gray-800">{{ number_format($pm->count) }} <span class="text-gray-400 font-normal text-xs">(৳{{ number_format($pm->amount,0) }})</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-red-400 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-6">No returns in this period.</p>
            @endforelse
        </div>
    </div>

    {{-- Returns by City --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Returns by City</h3>
        @php $maxCity = $returnsByCity->max('count') ?: 1; @endphp
        <div class="space-y-3">
            @forelse($returnsByCity as $city)
            @php $pct = round(($city->count / $maxCity) * 100); @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $city->shipping_city }}</span>
                    <span class="font-semibold text-gray-800">{{ number_format($city->count) }} <span class="text-gray-400 font-normal text-xs">(৳{{ number_format($city->amount,0) }})</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-orange-400 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-6">No returns in this period.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Returns Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Recent Returns & Cancellations</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Customer</th>
                    <th class="px-6 py-3 text-left">City</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Payment</th>
                    <th class="px-6 py-3 text-right">Amount</th>
                    <th class="px-6 py-3 text-right">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentReturns as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700">{{ $order->order_number }}</td>
                    <td class="px-6 py-3 text-gray-600">
                        <p>{{ $order->user?->name ?? $order->shipping_name }}</p>
                        <p class="text-xs text-gray-400">{{ $order->user?->email ?? '' }}</p>
                    </td>
                    <td class="px-6 py-3 text-gray-500 text-xs">{{ $order->shipping_city }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $order->status === 'refunded' ? 'bg-gray-100 text-gray-600' : 'bg-orange-100 text-orange-700' }}">{{ ucfirst($order->status) }}</span>
                    </td>
                    <td class="px-6 py-3 text-center text-xs uppercase text-gray-500">{{ $order->payment_method }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-red-600">-৳{{ number_format($order->total,2) }}</td>
                    <td class="px-6 py-3 text-right text-xs text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">No returns or cancellations in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($returnTrend);
    new Chart(document.getElementById('returnTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(m => m.month),
            datasets: [
                { label: 'Total Orders', data: trend.map(m => m.total_orders), backgroundColor: 'rgba(99,102,241,0.3)', borderRadius: 4, yAxisID: 'y' },
                { label: 'Returns', data: trend.map(m => m.returned), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 4, yAxisID: 'y' },
                { label: 'Revenue Lost', data: trend.map(m => parseFloat(m.lost_revenue)), type: 'line', borderColor: '#ef4444', backgroundColor: 'transparent', tension: 0.4, pointRadius: 3, yAxisID: 'y2' },
            ]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 } } }, y: { ticks: { font: { size:10 } } }, y2: { position: 'right', grid: { display:false }, ticks: { callback: v => '৳'+v.toLocaleString(), font: { size:10 } } } } }
    });

    new Chart(document.getElementById('returnTypePieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Cancelled', 'Refunded'],
            datasets: [{ data: [{{ $summary->cancelled_count ?? 0 }}, {{ $summary->refunded_count ?? 0 }}], backgroundColor: ['#f97316','#6b7280'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '65%' }
    });
});
</script>
@endsection
