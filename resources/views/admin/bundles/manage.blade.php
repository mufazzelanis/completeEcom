@extends('layouts.admin')
@section('title', 'Manage Bundle')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.bundles.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name }}</h1>
    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold">Bundle</span>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">
    {{-- Add Item --}}
    <div class="col-span-1">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Add Item to Bundle</h2>
            <form action="{{ route('admin.bundles.items.add', $product) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Product</label>
                    <select name="item_product_id" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select product...</option>
                        @foreach($allProducts as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Quantity</label>
                    <input type="number" name="quantity" value="1" min="1" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Item Discount %</label>
                    <input type="number" name="discount_pct" value="0" min="0" max="100" step="0.1"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Discount applied to this item when bought in bundle</p>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add to Bundle</button>
            </form>
        </div>

        {{-- Bundle Summary --}}
        <div class="bg-indigo-50 rounded-2xl p-5 mt-4">
            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-3">Bundle Price</p>
            <p class="text-2xl font-bold text-indigo-700">৳{{ number_format($product->price) }}</p>
            @if($product->sale_price)
            <p class="text-sm text-indigo-500 mt-1">৳{{ number_format($product->sale_price) }} on sale</p>
            @endif
            <p class="text-xs text-indigo-500 mt-3">
                Available to sell: <span class="font-semibold">{{ $product->available_stock }}</span>
                <span class="block text-indigo-400 mt-0.5">Auto-computed from component stock — lowest of (item stock ÷ quantity needed) across all items.</span>
            </p>
            <a href="{{ route('admin.products.edit', $product) }}" class="text-xs text-indigo-600 hover:text-indigo-800 underline mt-3 block">Edit bundle product →</a>
        </div>
    </div>

    {{-- Items List --}}
    <div class="col-span-2">
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Bundle Items ({{ $product->bundleItems->count() }})</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-center">Qty</th>
                    <th class="px-4 py-3 text-right">Unit Price</th>
                    <th class="px-4 py-3 text-center">Discount</th>
                    <th class="px-4 py-3 text-right">Effective</th>
                    <th class="px-4 py-3 text-center">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($product->bundleItems as $item)
                    <tr class="hover:bg-gray-50" x-data="{ editing: false }">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($item->itemProduct->image)
                                <img src="{{ Storage::url($item->itemProduct->image) }}" class="w-8 h-8 rounded-lg object-cover">
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800 text-xs">{{ $item->itemProduct->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $item->itemProduct->category?->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span x-show="!editing" class="font-semibold text-gray-700">{{ $item->quantity }}</span>
                            <form x-show="editing" x-cloak action="{{ route('admin.bundles.items.update', [$product, $item]) }}" method="POST" class="flex gap-1">
                                @csrf @method('PATCH')
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" class="w-12 border border-gray-200 rounded text-center text-xs px-1 py-0.5">
                                <input type="number" name="discount_pct" value="{{ $item->discount_pct }}" min="0" max="100" step="0.1" class="w-14 border border-gray-200 rounded text-center text-xs px-1 py-0.5" placeholder="Disc%">
                                <button type="submit" class="bg-indigo-600 text-white text-xs px-2 rounded">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">৳{{ number_format($item->itemProduct->sale_price ?? $item->itemProduct->price) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($item->discount_pct > 0)
                            <span class="bg-orange-100 text-orange-600 text-xs px-2 py-0.5 rounded-full">{{ $item->discount_pct }}% off</span>
                            @else
                            <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-green-600 text-xs">৳{{ number_format($item->effective_price) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" @click="editing = !editing" class="text-indigo-600 hover:text-indigo-800 text-xs">
                                    <span x-text="editing ? 'Cancel' : 'Edit'"></span>
                                </button>
                                <form action="{{ route('admin.bundles.items.remove', [$product, $item]) }}" method="POST" onsubmit="return confirm('Remove?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No items yet. Add products from the left panel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
