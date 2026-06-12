@extends('layouts.admin')
@section('title', 'Sales Report')

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.sales') }}" method="GET" class="flex flex-wrap items-center gap-3">
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
        <a href="{{ route('admin.reports.sales.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString(),'group_by'=>$groupBy]) }}"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            @foreach([['7d','7 Days'],['30d','30 Days'],['90d','90 Days'],['ytd','Year to Date']] as [$preset,$label])
            <a href="{{ route('admin.reports.sales', ['from' => match($preset){ '7d'=>now()->subDays(6)->toDateString(), '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $label }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['Total Revenue', '$'.number_format($summary->total_revenue ?? 0, 2), 'text-green-600', 'bg-green-50'],
        ['Total Orders', number_format($summary->total_orders ?? 0), 'text-indigo-600', 'bg-indigo-50'],
        ['Avg Order Value', '$'.number_format($summary->avg_order_value ?? 0, 2), 'text-blue-600', 'bg-blue-50'],
        ['Total Discounts', '$'.number_format($summary->total_discounts ?? 0, 2), 'text-orange-600', 'bg-orange-50'],
    ];
    @endphp
    @foreach($cards as [$label, $value, $tc, $bg])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Sales Trend Chart --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Sales Trend</h3>
        <canvas id="salesTrendChart" height="120"></canvas>
    </div>

    {{-- By Payment Method --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">By Payment Method</h3>
        <canvas id="paymentChart" height="180"></canvas>
        <div class="mt-4 space-y-2">
            @foreach($byPayment as $p)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 capitalize">{{ $p->payment_method ?? 'Unknown' }}</span>
                <span class="font-semibold text-gray-800">${{ number_format($p->revenue, 0) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- By Status --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Orders by Status</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100"><th class="pb-2 text-left">Status</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($byStatus as $s)
                <tr>
                    <td class="py-2"><span class="capitalize px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $s->status }}</span></td>
                    <td class="py-2 text-right font-medium">{{ number_format($s->count) }}</td>
                    <td class="py-2 text-right text-gray-600">${{ number_format($s->revenue, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($cancelledRevenue > 0)
        <p class="text-xs text-red-500 mt-3">* ${{ number_format($cancelledRevenue,2) }} lost to cancelled/refunded orders</p>
        @endif
    </div>

    {{-- By Category --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Category</h3>
        @php $totalCatRev = $byCategory->sum('revenue'); @endphp
        <div class="space-y-3">
            @forelse($byCategory as $cat)
            @php $pct = $totalCatRev > 0 ? round(($cat->revenue / $totalCatRev) * 100) : 0; @endphp
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-700">{{ $cat->category }}</span>
                    <span class="font-semibold text-gray-800">${{ number_format($cat->revenue,0) }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">No category data for this period.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Detailed Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Sales Data Table</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Period</th>
                <th class="px-6 py-3 text-right">Orders</th>
                <th class="px-6 py-3 text-right">Revenue</th>
                <th class="px-6 py-3 text-right">Discounts</th>
                <th class="px-6 py-3 text-right">Shipping</th>
                <th class="px-6 py-3 text-right">Avg Order</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($salesTrend as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700">{{ $row->period }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ number_format($row->orders) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">${{ number_format($row->revenue,2) }}</td>
                    <td class="px-6 py-3 text-right text-orange-600">-${{ number_format($row->discounts,2) }}</td>
                    <td class="px-6 py-3 text-right text-gray-500">${{ number_format($row->shipping,2) }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">${{ number_format($row->avg_order,2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No sales data for this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-100 font-semibold">
                <tr>
                    <td class="px-6 py-3 text-gray-700">Total</td>
                    <td class="px-6 py-3 text-right text-gray-800">{{ number_format($summary->total_orders ?? 0) }}</td>
                    <td class="px-6 py-3 text-right text-green-700">${{ number_format($summary->total_revenue ?? 0,2) }}</td>
                    <td class="px-6 py-3 text-right text-orange-600">-${{ number_format($summary->total_discounts ?? 0,2) }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">${{ number_format($summary->total_shipping ?? 0,2) }}</td>
                    <td class="px-6 py-3 text-right text-gray-700">${{ number_format($summary->avg_order_value ?? 0,2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($salesTrend);
    new Chart(document.getElementById('salesTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(r => r.period),
            datasets: [{
                label: 'Revenue ($)',
                data: trend.map(r => parseFloat(r.revenue)),
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderRadius: 4,
            },{
                label: 'Discounts ($)',
                data: trend.map(r => parseFloat(r.discounts)),
                backgroundColor: 'rgba(249,115,22,0.7)',
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 }, maxTicksLimit:15 } }, y: { ticks: { callback: v => '$'+v.toLocaleString(), font: { size:10 } } } } }
    });

    const pm = @json($byPayment);
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: pm.map(p => p.payment_method || 'Unknown'),
            datasets: [{ data: pm.map(p => parseFloat(p.revenue)), backgroundColor: ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, cutout: '60%' }
    });
});
</script>
@endsection
