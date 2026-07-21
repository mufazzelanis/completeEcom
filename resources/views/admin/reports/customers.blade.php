@extends('layouts.admin')
@section('title', 'Customer Analytics')

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Date filter --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.customers') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="{{ route('admin.reports.customers.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString()]) }}"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            @foreach([['30d','30 Days'],['90d','90 Days'],['ytd','This Year']] as [$p,$l])
            <a href="{{ route('admin.reports.customers',['from'=>match($p){'30d'=>now()->subDays(29)->toDateString(),'90d'=>now()->subDays(89)->toDateString(),'ytd'=>now()->startOfYear()->toDateString()},'to'=>now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Key metrics --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $totalBuyers = $returningBuyers + $firstTimeBuyers;
    $retentionRate = $totalBuyers > 0 ? round(($returningBuyers / $totalBuyers) * 100, 1) : 0;
    $cards = [
        ['Total Customers', number_format($totalCustomers), 'text-indigo-600','bg-indigo-50', 'New: '.number_format($newInRange).' in period'],
        ['Active Buyers', number_format($activeCustomers), 'text-green-600','bg-green-50', 'Placed order in period'],
        ['Avg Order Value', '৳'.number_format($avgOrderValue ?? 0,2), 'text-blue-600','bg-blue-50', number_format($ordersPerCustomer).' orders / customer'],
        ['Retention Rate', $retentionRate.'%', 'text-purple-600','bg-purple-50', number_format($returningBuyers).' returning buyers'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg,$sub])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $sub }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- New Customers Trend --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">New Customer Registrations</h3>
        <canvas id="newCustChart" height="120"></canvas>
    </div>

    {{-- New vs Returning --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">New vs Returning Buyers</h3>
        <canvas id="buyerTypeChart" height="180"></canvas>
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-indigo-500 rounded-full"></div><span class="text-gray-600">First-time buyers</span></div>
                <span class="font-semibold">{{ number_format($firstTimeBuyers) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-green-500 rounded-full"></div><span class="text-gray-600">Returning buyers</span></div>
                <span class="font-semibold">{{ number_format($returningBuyers) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2"><div class="w-3 h-3 bg-gray-300 rounded-full"></div><span class="text-gray-600">No orders yet</span></div>
                <span class="font-semibold">{{ number_format($customersNoOrders) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6">
    {{-- Top Customers --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">Top Customers by Spend</h3>
            <span class="text-xs text-gray-400">{{ $from->toDateString() }} – {{ $to->toDateString() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100"><th class="pb-2 text-left">Customer</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Avg</th><th class="pb-2 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($topCustomers as $i => $c)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-indigo-600 text-xs font-bold">{{ strtoupper(substr($c->user?->name ?? '?', 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $c->user?->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-400">{{ $c->user?->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($c->order_count) }}</td>
                        <td class="py-2.5 text-right text-gray-600">৳{{ number_format($c->avg_order,0) }}</td>
                        <td class="py-2.5 text-right font-semibold text-gray-800">৳{{ number_format($c->total_spent,2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-400">No data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- By City --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by City</h3>
        @php $maxCityRev = $byCity->max('revenue') ?: 1; @endphp
        <div class="space-y-3">
            @forelse($byCity as $city)
            @php $pct = round(($city->revenue / $maxCityRev) * 100); @endphp
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $city->shipping_city }}</span>
                    <span class="text-gray-500">{{ number_format($city->customers) }} customers · <span class="font-medium text-gray-800">৳{{ number_format($city->revenue,0) }}</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-purple-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-6">No city data available.</p>
            @endforelse
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($newCustomersTrend);
    new Chart(document.getElementById('newCustChart'), {
        type: 'line',
        data: {
            labels: trend.map(r => r.date),
            datasets: [{ label: 'New Customers', data: trend.map(r => r.count), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.4, pointRadius: 2 }]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 }, maxTicksLimit:12 } }, y: { beginAtZero: true, ticks: { stepSize:1, font: { size:10 } } } } }
    });

    new Chart(document.getElementById('buyerTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['First-time', 'Returning', 'No orders'],
            datasets: [{ data: [{{ $firstTimeBuyers }}, {{ $returningBuyers }}, {{ $customersNoOrders }}], backgroundColor: ['#6366f1','#10b981','#d1d5db'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '60%' }
    });
});
</script>
@endsection
