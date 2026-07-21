@extends('admin.reports.layout')
@section('report-title', 'Shipping Report')

@section('report-content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.shipping') }}" method="GET" class="flex flex-wrap items-center gap-3">
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
            <a href="{{ route('admin.reports.shipping', ['from' => match($p){ '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['Total Orders', number_format($summary->total_orders ?? 0), 'text-gray-800', 'bg-gray-50'],
        ['Shipping Revenue', '৳'.number_format($summary->total_shipping_revenue ?? 0,2), 'text-blue-700', 'bg-blue-50'],
        ['Delivery Rate', $deliveryRate.'%', 'text-green-700', 'bg-green-50'],
        ['Free Shipping', number_format($summary->free_shipping_count ?? 0).' orders', 'text-purple-700', 'bg-purple-50'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg])
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Shipping Trend Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Shipping Revenue & Deliveries — Last 12 Months</h3>
        <canvas id="shippingTrendChart" height="130"></canvas>
    </div>

    {{-- Key Stats --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
        <h3 class="font-semibold text-gray-800">Shipping Stats</h3>
        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl">
            <p class="text-sm font-medium text-blue-800">Avg Shipping Fee</p>
            <p class="font-bold text-blue-700">৳{{ number_format($summary->avg_shipping ?? 0,2) }}</p>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 rounded-xl">
            <p class="text-sm font-medium text-green-800">Delivered Orders</p>
            <p class="font-bold text-green-700">{{ number_format($summary->delivered_count ?? 0) }}</p>
        </div>
        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-xl">
            <p class="text-sm font-medium text-purple-800">In Transit</p>
            <p class="font-bold text-purple-700">{{ number_format($summary->shipped_count ?? 0) }}</p>
        </div>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
            <p class="text-sm font-medium text-gray-800">Free Shipping Revenue</p>
            <p class="font-bold text-gray-700">৳{{ number_format($freeShippingRevenue,0) }}</p>
        </div>
    </div>
</div>

{{-- By City Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Shipping by City</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">City</th>
                    <th class="px-6 py-3 text-right">Orders</th>
                    <th class="px-6 py-3 text-right">Revenue</th>
                    <th class="px-6 py-3 text-right">Shipping Fees</th>
                    <th class="px-6 py-3 text-right">Delivered</th>
                    <th class="px-6 py-3 text-right">Cancelled</th>
                    <th class="px-6 py-3 text-right">Delivery Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($byCity as $city)
                @php $dr = $city->orders > 0 ? round(($city->delivered / $city->orders) * 100) : 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700">{{ $city->shipping_city }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ number_format($city->orders) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">৳{{ number_format($city->revenue,0) }}</td>
                    <td class="px-6 py-3 text-right text-blue-600">৳{{ number_format($city->shipping_revenue,0) }}</td>
                    <td class="px-6 py-3 text-right text-green-600">{{ number_format($city->delivered) }}</td>
                    <td class="px-6 py-3 text-right text-red-500">{{ number_format($city->cancelled) }}</td>
                    <td class="px-6 py-3 text-right">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $dr >= 70 ? 'bg-green-100 text-green-700' : ($dr >= 40 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">{{ $dr }}%</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">No shipping data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- By Country --}}
@if($byCountry->count() > 1)
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Shipping by Country</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">Country</th>
                    <th class="px-6 py-3 text-right">Orders</th>
                    <th class="px-6 py-3 text-right">Revenue</th>
                    <th class="px-6 py-3 text-right">Shipping Fees</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($byCountry as $country)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700">{{ $country->shipping_country }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ number_format($country->orders) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">৳{{ number_format($country->revenue,0) }}</td>
                    <td class="px-6 py-3 text-right text-blue-600">৳{{ number_format($country->shipping_revenue,0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($shippingTrend);
    new Chart(document.getElementById('shippingTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(m => m.month),
            datasets: [
                { label: 'Shipping Revenue', data: trend.map(m => parseFloat(m.shipping_revenue)), backgroundColor: 'rgba(59,130,246,0.7)', borderRadius: 4, yAxisID: 'y' },
                { label: 'Delivered', data: trend.map(m => m.delivered), type: 'line', borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4, pointRadius: 3, yAxisID: 'y2' },
                { label: 'Total Orders', data: trend.map(m => m.orders), type: 'line', borderColor: '#6366f1', backgroundColor: 'transparent', tension: 0.4, pointRadius: 3, yAxisID: 'y2' },
            ]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 } } }, y: { ticks: { callback: v => '৳'+v.toLocaleString(), font: { size:10 } } }, y2: { position: 'right', grid: { display:false }, ticks: { font: { size:10 } } } } }
    });
});
</script>
@endsection
