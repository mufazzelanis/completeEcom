@extends('layouts.admin')
@section('title', 'Product Performance')

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Date filter --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.products') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="{{ route('admin.reports.products.download', ['from'=>$from->toDateString(),'to'=>$to->toDateString()]) }}"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export Excel
        </a>
        <div class="flex gap-2 ml-auto">
            @foreach([['30d','30 Days'],['90d','90 Days']] as [$p,$l])
            <a href="{{ route('admin.reports.products', ['from'=>match($p){'30d'=>now()->subDays(29)->toDateString(),'90d'=>now()->subDays(89)->toDateString()},'to'=>now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Top by Revenue --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Top Products by Revenue</h3>
        <p class="text-xs text-gray-400 mb-4">{{ $from->toDateString() }} – {{ $to->toDateString() }}</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100"><th class="pb-2 text-left">#</th><th class="pb-2 text-left">Product</th><th class="pb-2 text-right">Units</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($topByRevenue as $i => $p)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2.5 pr-3">
                            <span class="w-6 h-6 {{ $i < 3 ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500' }} rounded-full text-xs font-bold flex items-center justify-center">{{ $i+1 }}</span>
                        </td>
                        <td class="py-2.5">
                            <a href="{{ $p->product_id ? route('admin.products.edit', $p->product_id) : '#' }}" class="font-medium text-gray-800 hover:text-indigo-600">{{ $p->product_name }}</a>
                        </td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($p->qty_sold) }}</td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($p->orders) }}</td>
                        <td class="py-2.5 text-right font-semibold text-gray-800">${{ number_format($p->revenue,2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">No sales data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Category Performance --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Category Performance</h3>
        <canvas id="catChart" height="220"></canvas>
        <div class="mt-3 space-y-1 max-h-32 overflow-y-auto">
            @foreach($categoryPerf->take(6) as $cat)
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600">{{ $cat->category }}</span>
                <span class="font-medium">${{ number_format($cat->revenue,0) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Top by Quantity --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Top by Units Sold</h3>
        <div class="space-y-3">
            @forelse($topByQty as $i => $p)
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-5 text-center">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-700 truncate">{{ $p->product_name }}</p>
                    <div class="w-full bg-gray-100 rounded-full h-1 mt-1">
                        <div class="bg-green-500 h-1 rounded-full" style="width:{{ $topByQty->max('qty_sold') > 0 ? round(($p->qty_sold/$topByQty->max('qty_sold'))*100) : 0 }}%"></div>
                    </div>
                </div>
                <span class="text-sm font-semibold text-gray-700 flex-shrink-0">{{ number_format($p->qty_sold) }}</span>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">No data.</p>
            @endforelse
        </div>
    </div>

    {{-- Most Viewed --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Most Viewed Products</h3>
        <div class="space-y-3">
            @foreach($mostViewed as $i => $p)
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-5 text-center">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('admin.products.edit', $p) }}" class="text-sm text-gray-700 hover:text-indigo-600 block truncate">{{ $p->name }}</a>
                    <p class="text-xs text-gray-400">{{ $p->category?->name }}</p>
                </div>
                <span class="text-sm font-semibold text-indigo-600 flex-shrink-0">{{ number_format($p->views) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Never Sold --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Never Sold (Active + In Stock)</h3>
        <p class="text-xs text-gray-400 mb-4">Products with stock that have never had an order</p>
        @forelse($neverSold as $p)
        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div class="min-w-0">
                <a href="{{ route('admin.products.edit', $p) }}" class="text-sm text-gray-700 hover:text-indigo-600 truncate block">{{ $p->name }}</a>
                <p class="text-xs text-gray-400">{{ $p->category?->name }} · stock: {{ $p->stock }}</p>
            </div>
            <span class="text-sm text-gray-500 ml-2">${{ number_format($p->price,2) }}</span>
        </div>
        @empty
        <p class="text-green-600 text-sm text-center py-4">All products with stock have been sold!</p>
        @endforelse
    </div>
</div>

{{-- Low Stock Warning --}}
@if($lowStock->count())
<div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-orange-400">
    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Low Stock Alert ({{ $lowStock->count() }} products)
    </h3>
    <div class="grid grid-cols-3 gap-3">
        @foreach($lowStock as $p)
        <div class="flex items-center justify-between bg-orange-50 rounded-xl px-4 py-2.5">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $p->name }}</p>
                <p class="text-xs text-gray-500">{{ $p->category?->name }}</p>
            </div>
            <span class="text-sm font-bold text-orange-600 ml-2 flex-shrink-0">{{ $p->stock }} left</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cats = @json($categoryPerf->take(8));
    const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: {
            labels: cats.map(c => c.category),
            datasets: [{ data: cats.map(c => parseFloat(c.revenue)), backgroundColor: palette, borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '55%' }
    });
});
</script>
@endsection
