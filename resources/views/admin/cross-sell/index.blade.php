@extends('layouts.admin')
@section('title', 'Cross-Sell & Upsell')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Cross-Sell & Upsell</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $totalWithRecs }} products have recommendations configured
        </p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <p class="text-sm text-gray-500">Products with recommendations. <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-700">Open any product</a> to add recommendations.</p>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase tracking-wider">
            <th class="px-6 py-3 text-left">Product</th>
            <th class="px-6 py-3 text-center">Cross-Sells</th>
            <th class="px-6 py-3 text-center">Upsells</th>
            <th class="px-6 py-3 text-center">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" class="w-9 h-9 rounded-xl object-cover">
                        @endif
                        <div>
                            <p class="font-medium text-gray-800">{{ $product->name }}</p>
                            <p class="text-xs text-gray-400">৳{{ number_format($product->price) }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-lg font-bold {{ $product->cross_sell_count > 0 ? 'text-green-600' : 'text-gray-300' }}">{{ $product->cross_sell_count }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-lg font-bold {{ $product->upsell_count > 0 ? 'text-indigo-600' : 'text-gray-300' }}">{{ $product->upsell_count }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <a href="{{ route('admin.cross-sell.manage', $product) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Manage</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-16 text-center text-gray-400">No recommendations configured yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>
</div>
@endsection
