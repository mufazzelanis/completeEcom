@extends('layouts.admin')
@section('title', 'Inventory Report')

@section('content')
{{-- Nav --}}
<div class="flex gap-2 mb-6 flex-wrap">
    @foreach([['reports.index','Overview'],['reports.sales','Sales'],['reports.revenue','Revenue'],['reports.products','Products'],['reports.customers','Customers'],['reports.inventory','Inventory']] as [$r,$l])
    <a href="{{ route('admin.'.$r) }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.'.$r) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 shadow-sm' }}">{{ $l }}</a>
    @endforeach
</div>

{{-- Download --}}
<div class="flex justify-end mb-4">
    <a href="{{ route('admin.reports.inventory.download') }}"
       class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export Excel
    </a>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @php
    $cards = [
        ['Total Active', number_format($totalProducts), 'text-gray-700','bg-gray-50'],
        ['In Stock', number_format($inStockCount), 'text-green-700','bg-green-50'],
        ['Out of Stock', number_format($outOfStockCount), 'text-red-700','bg-red-50'],
        ['Low Stock', number_format($lowStockCount), 'text-orange-700','bg-orange-50'],
        ['Total Stock Value', '৳'.number_format($totalStockValue,0), 'text-indigo-700','bg-indigo-50'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Stock Distribution Chart --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Stock Distribution</h3>
        <canvas id="stockDistChart" height="200"></canvas>
        <div class="mt-4 space-y-1">
            @foreach($stockBins as $range => $count)
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ $range }} units</span>
                <span class="font-medium text-gray-800">{{ number_format($count) }} products</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By Category --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Stock by Category</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                    <th class="pb-2 text-left">Category</th>
                    <th class="pb-2 text-right">Products</th>
                    <th class="pb-2 text-right">Total Stock</th>
                    <th class="pb-2 text-right">Stock Value</th>
                    <th class="pb-2 text-left pl-4">Value Share</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @php $totalValue = $byCategory->sum('stock_value') ?: 1; @endphp
                    @foreach($byCategory as $cat)
                    @php $pct = round(($cat->stock_value / $totalValue) * 100); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-2.5 font-medium text-gray-700">{{ $cat->category }}</td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($cat->product_count) }}</td>
                        <td class="py-2.5 text-right text-gray-600">{{ number_format($cat->total_stock) }}</td>
                        <td class="py-2.5 text-right font-semibold text-gray-800">৳{{ number_format($cat->stock_value,0) }}</td>
                        <td class="py-2.5 pl-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5 w-24">
                                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- Low Stock --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-orange-400">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Low Stock ({{ $lowStock->count() }})
        </h3>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            @forelse($lowStock as $p)
            <div class="flex items-center justify-between bg-orange-50 rounded-xl px-4 py-2.5">
                <div class="min-w-0">
                    <a href="{{ route('admin.products.edit', $p) }}" class="text-sm font-medium text-gray-800 hover:text-indigo-600 block truncate">{{ $p->name }}</a>
                    <p class="text-xs text-gray-500">{{ $p->category?->name }} · threshold: {{ $p->low_stock_threshold ?? 5 }}</p>
                </div>
                <span class="text-sm font-bold text-orange-600 ml-3 flex-shrink-0">{{ $p->stock }} left</span>
            </div>
            @empty
            <p class="text-green-600 text-sm text-center py-4">No low stock products!</p>
            @endforelse
        </div>
    </div>

    {{-- Reorder Priority --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Reorder Priority</h3>
        <p class="text-xs text-gray-400 mb-4">Fast-selling products — order soon if stock is low</p>
        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            @forelse($reorderPriority as $i => $item)
            <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-5 h-5 bg-indigo-100 text-indigo-600 rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-400">{{ number_format($item->sold_30d) }} sold in 30d · stock: {{ $item->product?->stock ?? '?' }}</p>
                    </div>
                </div>
                @php
                    $stock = $item->product?->stock ?? 0;
                    $threshold = $item->product?->low_stock_threshold ?? 5;
                @endphp
                <span class="text-xs font-semibold flex-shrink-0 ml-2 {{ $stock <= $threshold ? 'text-red-600' : ($stock <= $threshold * 4 ? 'text-orange-600' : 'text-green-600') }}">
                    {{ $stock <= $threshold ? 'CRITICAL' : ($stock <= $threshold * 4 ? 'LOW' : 'OK') }}
                </span>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-4">No sales data in last 30 days.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Out of Stock --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden border-l-4 border-red-400">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Out of Stock ({{ $outOfStockCount }} total)
        </h3>
        <a href="{{ route('admin.stock-management.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Manage Stock →</a>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
            <th class="px-6 py-3 text-left">Product</th>
            <th class="px-6 py-3 text-left">Category</th>
            <th class="px-6 py-3 text-right">Price</th>
            <th class="px-6 py-3 text-center">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($outOfStock as $p)
            <tr class="hover:bg-red-50 transition">
                <td class="px-6 py-3 font-medium text-gray-800">{{ $p->name }}</td>
                <td class="px-6 py-3 text-gray-500">{{ $p->category?->name }}</td>
                <td class="px-6 py-3 text-right text-gray-700">৳{{ number_format($p->price,2) }}</td>
                <td class="px-6 py-3 text-center">
                    <a href="{{ route('admin.products.edit', $p) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Update Stock</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-10 text-center text-green-600">All products are in stock!</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($outOfStock->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $outOfStock->links() }}</div>
    @endif
</div>

@if($inactiveWithStock->count())
<div class="bg-white rounded-2xl shadow-sm p-6 mt-6">
    <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Inactive Products with Stock ({{ $inactiveWithStock->count() }})
    </h3>
    <div class="grid grid-cols-2 gap-3">
        @foreach($inactiveWithStock as $p)
        <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5">
            <div class="min-w-0">
                <a href="{{ route('admin.products.edit', $p) }}" class="text-sm font-medium text-gray-700 hover:text-indigo-600 truncate block">{{ $p->name }}</a>
                <p class="text-xs text-gray-400">{{ $p->category?->name }}</p>
            </div>
            <span class="text-sm text-gray-500 ml-2">{{ number_format($p->stock) }} units</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('stockDistChart'), {
        type: 'bar',
        data: {
            labels: @json(array_keys($stockBins)),
            datasets: [{ label: 'Products', data: @json(array_values($stockBins)), backgroundColor: ['#ef4444','#f59e0b','#10b981','#6366f1','#8b5cf6'], borderRadius: 6 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, scales: { x: { grid: { display:false }, ticks: { font: { size:10 } } }, y: { beginAtZero:true, ticks: { stepSize:1, font: { size:10 } } } } }
    });
});
</script>
@endsection
