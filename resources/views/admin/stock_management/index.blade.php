@extends('layouts.admin')
@section('title', 'Stock Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Management</h1>
        <p class="text-sm text-gray-500 mt-1">Update product stock levels and view history</p>
    </div>
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.stock-reasons.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            Manage Reasons
        </a>
        <a href="{{ route('admin.stock-adjustments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            View Stock History →
        </a>
    </div>
</div>

<datalist id="stock-reason-options">
    @foreach($reasons as $reason)
        <option value="{{ $reason->label }}">
    @endforeach
</datalist>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Filters: one-click stock chips (with how many products each would show, under the other
     filters) + search / category / brand / status / exact range / sort. Then "Select all N
     matching" below → one bulk change for the whole filtered list. --}}
@php
    $chip = fn ($key) => request()->fullUrlWithQuery(['stock_filter' => request('stock_filter') === $key ? null : $key, 'min' => null, 'max' => null, 'page' => null]);
    $chipColors = ['out' => '#ef4444', 's1' => '#f97316', 's2' => '#f97316', 's3' => '#f59e0b', 'low' => '#f59e0b', 'b6' => '#eab308', 'b11' => '#84cc16', 'ok' => '#10b981', 'below' => '#e11d48', 'fast' => '#8b5cf6'];
    $chips = collect(\App\Http\Controllers\Admin\StockManagementController::STOCK_BUCKETS)->map(fn ($b) => $b[0])
        ->put('below', 'Below its alert level')->put('fast', '🔥 Running out soon');
    $active = request('stock_filter');
@endphp
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-4 mb-4 space-y-4">
    <div class="flex flex-wrap gap-2">
        <a href="{{ request()->fullUrlWithQuery(['stock_filter' => null, 'min' => null, 'max' => null, 'page' => null]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition"
           style="{{ !$active && !request()->filled('min') && !request()->filled('max') ? 'background:#4f46e5;color:#fff' : 'background:#4f46e51a;color:#6366f1' }}">
            All <span class="text-xs opacity-80">{{ number_format($counts['all']) }}</span>
        </a>
        @foreach($chips as $key => $label)
            @php $c = $chipColors[$key]; $on = $active === $key; @endphp
            <a href="{{ $chip($key) }}" title="{{ $key === 'fast' ? 'At the last 30 days\' sales speed, these run out within 14 days' : ($key === 'below' ? 'Stock at or below the product\'s own low-stock alert level' : '') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition {{ $counts[$key] ? '' : 'opacity-50' }}"
               style="{{ $on ? "background:{$c};color:#fff" : "background:{$c}1a;color:{$c}" }}">
                {{ $label }} <span class="text-xs {{ $on ? 'opacity-90' : 'opacity-80' }}">{{ number_format($counts[$key]) }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="flex flex-wrap gap-3 items-end">
        @if($active)<input type="hidden" name="stock_filter" value="{{ $active }}">@endif
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or SKU…"
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Stock between</label>
            <div class="flex items-center gap-1.5">
                <input type="number" name="min" min="0" value="{{ request('min') }}" placeholder="Min" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-20 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <span class="text-gray-400">–</span>
                <input type="number" name="max" min="0" value="{{ request('max') }}" placeholder="Max" class="border border-gray-200 rounded-xl px-3 py-2 text-sm w-20 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Category</label>
            <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Brand</label>
            <select name="brand" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 max-w-[11rem]">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Status</label>
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Any</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Hidden</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Sort</label>
            <select name="sort" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Lowest stock first</option>
                <option value="stock_desc" @selected(request('sort') === 'stock_desc')>Highest stock first</option>
                <option value="selling" @selected(request('sort') === 'selling')>Best selling (30 days)</option>
                <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
            </select>
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','stock_filter','category','brand','status','sort','min','max']))
        <a href="{{ route('admin.stock-management.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
    <p class="text-xs text-gray-400">Tip: pick a filter, then <strong>Select all {{ number_format($products->total()) }} matching</strong> below and apply one change to all of them at once — e.g. everything with 1–3 left → increase by 20.</p>
</div>

<div x-data="{
        selected: [],
        pageIds: [{{ $products->pluck('id')->implode(',') }}],
        totalMatching: {{ $products->total() }},
        selectingAll: false,
        async selectAllMatching() {
            this.selectingAll = true;
            try {
                const res = await fetch('{{ route('admin.stock-management.ids', request()->query()) }}');
                const data = await res.json();
                this.selected = data.ids;
            } finally {
                this.selectingAll = false;
            }
        },
    }">
    {{-- Bulk Apply — one action (set / increase / decrease) applied to every selected
         product at once, e.g. "a new shipment of 50 units landed for this whole category"
         or "these got recalled" — the per-row form below is still there for one-off,
         differing-per-product corrections. --}}
    <form action="{{ route('admin.stock-management.bulk-apply') }}" method="POST"
          @submit.prevent="showConfirmModal('Apply this to ' + selected.length + ' product(s)?').then(ok => { if (ok) $el.submit(); })">
        @csrf
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
        <div x-show="selected.length > 0" x-cloak
             class="mb-4 bg-indigo-50 border border-indigo-100 rounded-2xl px-4 py-3 flex flex-wrap items-end gap-3">
            <span class="text-sm font-medium text-indigo-800 pb-2" x-text="selected.length + ' selected'"></span>
            <button type="button" x-show="totalMatching > pageIds.length && pageIds.every(id => selected.includes(id)) && selected.length < totalMatching"
                    x-cloak @click="selectAllMatching()" :disabled="selectingAll"
                    class="text-sm text-indigo-700 hover:text-indigo-900 font-medium underline disabled:opacity-50 pb-2">
                <span x-show="!selectingAll" x-text="'Select all ' + totalMatching + ' matching products'"></span>
                <span x-show="selectingAll" x-cloak>Selecting&hellip;</span>
            </button>

            <div>
                <label class="block text-xs text-indigo-700 mb-1">Action</label>
                <select name="mode" class="border border-indigo-200 rounded-lg px-3 py-1.5 text-sm bg-white">
                    <option value="increase">Increase stock by</option>
                    <option value="decrease">Decrease stock by</option>
                    <option value="set">Set stock to</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-indigo-700 mb-1">Quantity</label>
                <input type="number" name="value" min="0" required
                    class="w-24 border border-indigo-200 rounded-lg px-3 py-1.5 text-sm">
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs text-indigo-700 mb-1">Reason (optional)</label>
                <input type="text" name="reason" list="stock-reason-options" placeholder="e.g. New shipment arrived"
                    class="w-full border border-indigo-200 rounded-lg px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                Apply
            </button>
            <button type="button" @click="selected = []" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2" title="Clear selection">
                Clear
            </button>
        </div>
    </form>

    <form action="{{ route('admin.stock-management.update') }}" method="POST" id="bulkStockForm">
    @csrf
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">Check a few rows to bulk-apply one change above, or edit quantities inline below and click <strong>Save All Changes</strong>.</p>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                Save All Changes
            </button>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox"
                               :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(id))"
                               @change="selected = $event.target.checked ? [...new Set([...selected, ...pageIds])] : selected.filter(id => !pageIds.includes(id))"
                               class="rounded text-indigo-600" title="Select all on this page">
                    </th>
                    <th class="px-6 py-3 text-left">Product</th>
                    <th class="px-6 py-3 text-left">SKU</th>
                    <th class="px-6 py-3 text-center w-28">Current</th>
                    <th class="px-6 py-3 text-center w-24" title="Units sold in the last 30 days (cancelled/refunded orders not counted)">Sold 30d</th>
                    <th class="px-6 py-3 text-center w-36">New Stock</th>
                    <th class="px-6 py-3 text-left">Reason</th>
                    <th class="px-6 py-3 text-right w-24">Quick Set</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $index => $product)
                <tr class="hover:bg-gray-50/50 transition" x-data="{ stock: {{ $product->stock }} }">
                    <input type="hidden" name="products[{{ $index }}][id]" value="{{ $product->id }}">
                    <td class="px-6 py-3">
                        <input type="checkbox" value="{{ $product->id }}"
                               :checked="selected.includes({{ $product->id }})"
                               @change="$event.target.checked ? selected.push({{ $product->id }}) : selected = selected.filter(i => i !== {{ $product->id }})"
                               class="rounded text-indigo-600">
                    </td>
                    <td class="px-6 py-3">
                        <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-400">{{ $product->category->name ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-3 text-xs font-mono text-gray-500">{{ $product->sku ?? '—' }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="text-sm font-bold {{ $product->stock === 0 ? 'text-red-600' : ($product->stock <= 5 ? 'text-orange-700' : 'text-gray-900') }}">
                            {{ $product->stock }}
                        </span>
                        @if($product->stock === 0)
                        <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full">Out</span>
                        @elseif($product->stock <= 5)
                        <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded-full">Low</span>
                        @endif
                        @if(!$product->is_active)<span class="block text-[10px] text-gray-400">hidden</span>@endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        @php
                            $sold = (int) $product->sold_30d;
                            $daysLeft = $sold > 0 ? (int) floor($product->stock / ($sold / 30)) : null;
                        @endphp
                        <span class="text-sm font-semibold {{ $sold ? 'text-gray-900 dark:text-gray-100' : 'text-gray-300' }}">{{ $sold }}</span>
                        @if($daysLeft !== null)
                            <span class="block text-[11px] font-medium" style="color: {{ $daysLeft <= 7 ? '#dc2626' : ($daysLeft <= 14 ? '#d97706' : '#6b7280') }};"
                                  title="At this pace">~{{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} left</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        <input type="number" name="products[{{ $index }}][stock]" min="0"
                            x-model="stock"
                            class="w-24 text-center border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            :class="stock != {{ $product->stock }} ? 'border-indigo-400 bg-indigo-50' : ''">
                    </td>
                    <td class="px-6 py-3">
                        <input type="text" name="products[{{ $index }}][reason]" placeholder="Optional note…" list="stock-reason-options"
                            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 text-gray-700">
                    </td>
                    <td class="px-6 py-3 text-right">
                        <div class="flex justify-end gap-1">
                            <button type="button" @click="stock = Math.max(0, stock - 1)"
                                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-100 text-sm font-bold">−</button>
                            <button type="button" @click="stock = stock + 1"
                                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-100 text-sm font-bold">+</button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <div>{{ $products->withQueryString()->links() }}</div>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                Save All Changes
            </button>
        </div>
        @endif
    </div>
    </form>
</div>
@endsection
