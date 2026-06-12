@extends('layouts.admin')
@section('title', 'Revenue Report')

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Date filter --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.revenue') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="{{ route('admin.reports.revenue.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString()]) }}"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            @foreach([['30d','30 Days'],['90d','90 Days'],['ytd','This Year']] as [$p,$l])
            <a href="{{ route('admin.reports.revenue', ['from' => match($p){ '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @php
    $net = ($summary->gross_revenue ?? 0) - ($summary->total_discounts ?? 0);
    $cards = [
        ['Gross Revenue', '$'.number_format($summary->gross_revenue ?? 0,2), 'text-green-700', 'bg-green-50'],
        ['Total Discounts', '-$'.number_format($summary->total_discounts ?? 0,2), 'text-orange-700', 'bg-orange-50'],
        ['Net Revenue', '$'.number_format($net,2), 'text-indigo-700', 'bg-indigo-50'],
        ['Shipping Rev.', '$'.number_format($summary->shipping_revenue ?? 0,2), 'text-blue-700', 'bg-blue-50'],
        ['Tax Collected', '$'.number_format($summary->tax_collected ?? 0,2), 'text-purple-700', 'bg-purple-50'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-xl font-bold {{ $tc }}">{{ $value }}</p>
        @if($label === 'Gross Revenue')
        <p class="text-xs mt-1 {{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-500' }}">
            {{ $revenueGrowth >= 0 ? '↑' : '↓' }} {{ abs($revenueGrowth) }}% vs prev period
        </p>
        @endif
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Monthly Revenue Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Monthly Revenue — Last 12 Months</h3>
        <canvas id="monthlyChart" height="120"></canvas>
    </div>

    {{-- By Category Pie --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Category</h3>
        <canvas id="catPieChart" height="180"></canvas>
        <div class="mt-3 space-y-1 max-h-36 overflow-y-auto">
            @foreach($byCategory->take(8) as $cat)
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600 truncate">{{ $cat->category }}</span>
                <span class="font-medium text-gray-800 ml-2">${{ number_format($cat->revenue,0) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- Revenue by Brand --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Brand</h3>
        @php $totalBrandRev = $byBrand->sum('revenue'); @endphp
        <div class="space-y-3">
            @forelse($byBrand as $brand)
            @php $pct = $totalBrandRev > 0 ? round(($brand->revenue/$totalBrandRev)*100) : 0; @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $brand->brand }}</span>
                    <span class="font-semibold text-gray-800">${{ number_format($brand->revenue,0) }} <span class="text-gray-400 font-normal text-xs">({{ $pct }}%)</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-6">No brand data available.</p>
            @endforelse
        </div>
    </div>

    {{-- Coupon Impact --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Coupon & Discount Impact</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-orange-800">Total Discounts Given</p>
                    <p class="text-xs text-orange-600 mt-0.5">{{ number_format($couponImpact->orders_with_coupon ?? 0) }} orders used coupons</p>
                </div>
                <p class="text-xl font-bold text-orange-700">-${{ number_format($couponImpact->total_discount ?? 0,2) }}</p>
            </div>
            <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-indigo-800">Avg Discount per Order</p>
                </div>
                <p class="text-xl font-bold text-indigo-700">${{ number_format($couponImpact->avg_discount ?? 0,2) }}</p>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-gray-800">Coupon Usage Rate</p>
                    <p class="text-xs text-gray-500 mt-0.5">Orders using a coupon code</p>
                </div>
                <p class="text-xl font-bold text-gray-700">{{ $couponRate }}%</p>
            </div>
            @php
            $discountPct = ($summary->gross_revenue ?? 0) > 0 ? round((($summary->total_discounts ?? 0) / $summary->gross_revenue) * 100, 1) : 0;
            @endphp
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-xl">
                <div>
                    <p class="text-sm font-medium text-red-800">Discount % of Revenue</p>
                </div>
                <p class="text-xl font-bold text-red-700">{{ $discountPct }}%</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const monthly = @json($monthlyTrend);
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthly.map(m => m.month),
            datasets: [{
                label: 'Revenue',
                data: monthly.map(m => parseFloat(m.revenue)),
                backgroundColor: 'rgba(99,102,241,0.75)',
                borderRadius: 5,
            },{
                label: 'Discounts',
                data: monthly.map(m => parseFloat(m.discounts)),
                backgroundColor: 'rgba(249,115,22,0.65)',
                borderRadius: 5,
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font:{ size:10 } } }, y: { stacked: false, ticks: { callback: v => '$'+v.toLocaleString(), font: { size:10 } } } } }
    });

    const cats = @json($byCategory->take(8));
    const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];
    new Chart(document.getElementById('catPieChart'), {
        type: 'doughnut',
        data: {
            labels: cats.map(c => c.category),
            datasets: [{ data: cats.map(c => parseFloat(c.revenue)), backgroundColor: palette, borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '60%' }
    });
});
</script>
@endsection
