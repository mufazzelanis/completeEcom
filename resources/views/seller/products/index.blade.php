@extends('layouts.seller')
@section('title', 'My Products')
@section('pageTitle', 'My Products')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-800">My Products</h1>
    <a href="{{ route('seller.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        + Add Product
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search your products…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Search</button>
        @if(request('search'))<a href="{{ route('seller.products.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Product</th>
                <th class="px-5 py-3 text-left">Category</th>
                <th class="px-5 py-3 text-right">Price</th>
                <th class="px-5 py-3 text-center">Stock</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            @if($product->image)<img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">@endif
                        </div>
                        <p class="font-medium text-gray-800 truncate max-w-56">{{ $product->name }}</p>
                    </div>
                </td>
                <td class="px-5 py-3 text-gray-600">{{ $product->category->name ?? '—' }}</td>
                <td class="px-5 py-3 text-right">
                    @if($product->sale_price)
                        <p class="font-semibold text-red-600">৳{{ number_format($product->sale_price) }}</p>
                        <p class="text-xs text-gray-400 line-through">৳{{ number_format($product->price) }}</p>
                    @else
                        <p class="font-semibold text-gray-900">৳{{ number_format($product->price) }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">{{ $product->stock }}</td>
                <td class="px-5 py-3 text-center">
                    @php
                        $badge = match($product->approval_status) {
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default => 'bg-green-100 text-green-700',
                        };
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($product->approval_status) }}</span>
                    @if($product->approval_status === 'rejected' && $product->rejection_reason)
                    <p class="text-xs text-red-500 mt-1 max-w-40 mx-auto" title="{{ $product->rejection_reason }}">{{ Str::limit($product->rejection_reason, 40) }}</p>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('seller.products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                        <form action="{{ route('seller.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-16 text-center text-gray-400">No products found. <a href="{{ route('seller.products.create') }}" class="text-indigo-600">Add one</a>.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $products->links() }}</div>
</div>
@endsection
