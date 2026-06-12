@extends('admin.reports.layout')
@section('report-title', 'Marketing & Coupon Report')

@section('report-content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.marketing') }}" method="GET" class="flex flex-wrap items-center gap-3">
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
            <a href="{{ route('admin.reports.marketing', ['from' => match($p){ '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Coupon Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['Coupon Orders', number_format($couponSummary->orders_with_coupon), 'text-indigo-700', 'bg-indigo-50', 'Out of '.number_format($totalOrders).' total ('.$couponRate.'%)'],
        ['Total Discounts', '$'.number_format($couponSummary->total_discount,2), 'text-orange-700', 'bg-orange-50', 'Avg $'.number_format($couponSummary->avg_discount,2).' per coupon order'],
        ['Revenue w/ Coupon', '$'.number_format($couponSummary->revenue_with_coupon,2), 'text-purple-700', 'bg-purple-50', 'Avg order $'.number_format($couponSummary->avg_order_with_coupon,2)],
        ['Revenue w/o Coupon', '$'.number_format($couponSummary->revenue_without_coupon,2), 'text-green-700', 'bg-green-50', 'Avg order $'.number_format($couponSummary->avg_order_without,2)],
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
    {{-- Discount Trend Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Discount Trend — Last 12 Months</h3>
        <canvas id="discountTrendChart" height="130"></canvas>
    </div>

    {{-- Coupon vs No Coupon --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Coupon Usage Rate</h3>
        <canvas id="couponPieChart" height="160"></canvas>
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl">
                <p class="text-sm font-medium text-indigo-800">With Coupon</p>
                <p class="font-bold text-indigo-700">{{ $couponRate }}%</p>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <p class="text-sm font-medium text-gray-700">Without Coupon</p>
                <p class="font-bold text-gray-600">{{ 100 - $couponRate }}%</p>
            </div>
        </div>
    </div>
</div>

{{-- AOV Comparison --}}
<div class="grid grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col items-center justify-center text-center">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Avg Order — With Coupon</p>
        <p class="text-3xl font-bold text-indigo-600">${{ number_format($couponSummary->avg_order_with_coupon,2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Including discount</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col items-center justify-center text-center">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Avg Order — No Coupon</p>
        <p class="text-3xl font-bold text-green-600">${{ number_format($couponSummary->avg_order_without,2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Full price</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col items-center justify-center text-center">
        @php $discountPct = ($couponSummary->revenue_with_coupon + $couponSummary->revenue_without_coupon) > 0
            ? round(($couponSummary->total_discount / ($couponSummary->revenue_with_coupon + $couponSummary->revenue_without_coupon + $couponSummary->total_discount)) * 100, 1) : 0;
        @endphp
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Discount % of Gross</p>
        <p class="text-3xl font-bold text-orange-600">{{ $discountPct }}%</p>
        <p class="text-xs text-gray-400 mt-1">Revenue given away</p>
    </div>
</div>

{{-- Top Coupons Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Top Coupons</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Coupon Code</th>
                    <th class="px-6 py-3 text-right">Uses</th>
                    <th class="px-6 py-3 text-right">Total Discount</th>
                    <th class="px-6 py-3 text-right">Revenue Generated</th>
                    <th class="px-6 py-3 text-right">Avg Discount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($topCoupons as $i => $coupon)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-400 text-xs">{{ $i+1 }}</td>
                    <td class="px-6 py-3 font-mono font-semibold text-indigo-700">{{ strtoupper($coupon->coupon_code) }}</td>
                    <td class="px-6 py-3 text-right font-medium text-gray-800">{{ number_format($coupon->uses) }}</td>
                    <td class="px-6 py-3 text-right text-orange-600 font-semibold">-${{ number_format($coupon->total_discount,2) }}</td>
                    <td class="px-6 py-3 text-right text-green-700 font-semibold">${{ number_format($coupon->revenue,2) }}</td>
                    <td class="px-6 py-3 text-right text-gray-500">${{ number_format($coupon->avg_discount,2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No coupon data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($discountTrend);
    new Chart(document.getElementById('discountTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(m => m.month),
            datasets: [
                { label: 'Revenue', data: trend.map(m => parseFloat(m.revenue)), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 4, yAxisID: 'y' },
                { label: 'Discounts Given', data: trend.map(m => parseFloat(m.total_discount)), backgroundColor: 'rgba(249,115,22,0.7)', borderRadius: 4, yAxisID: 'y' },
                { label: 'Coupon Orders', data: trend.map(m => m.coupon_orders), type: 'line', borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,0.1)', fill: false, tension: 0.4, pointRadius: 3, yAxisID: 'y2' },
            ]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 } } }, y: { ticks: { callback: v => '$'+v.toLocaleString(), font: { size:10 } } }, y2: { position: 'right', grid: { display:false }, ticks: { font: { size:10 } } } } }
    });

    const withC  = {{ $couponSummary->orders_with_coupon }};
    const withoutC = {{ $couponSummary->orders_without_coupon }};
    new Chart(document.getElementById('couponPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['With Coupon', 'Without Coupon'],
            datasets: [{ data: [withC, withoutC], backgroundColor: ['#6366f1','#e2e8f0'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '65%' }
    });
});
</script>
@endsection
