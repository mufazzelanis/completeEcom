@extends('layouts.admin')
@section('title', 'Bundle Offers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Bundle Offers</h1>
        <p class="text-sm text-gray-500 mt-0.5">Products with type "bundle" — manage their included items</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Bundle Product
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Bundle</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-right">Price</th>
                <th class="px-6 py-3 text-center">Items</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($bundles as $bundle)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        @if($bundle->image)
                        <img src="{{ Storage::url($bundle->image) }}" class="w-10 h-10 rounded-xl object-cover">
                        @else
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-800">{{ $bundle->name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">SKU: {{ $bundle->sku ?? '—' }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ $bundle->category?->name }}</td>
                <td class="px-6 py-4 text-right">
                    <p class="font-semibold text-gray-800">৳{{ number_format($bundle->price) }}</p>
                    @if($bundle->sale_price)<p class="text-xs text-green-600">৳{{ number_format($bundle->sale_price) }} sale</p>@endif
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-lg font-bold {{ $bundle->bundle_items_count > 0 ? 'text-indigo-600' : 'text-red-400' }}">
                        {{ $bundle->bundle_items_count }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $bundle->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $bundle->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ route('admin.bundles.manage', $bundle) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Manage Items</a>
                        <a href="{{ route('admin.products.edit', $bundle) }}" class="text-gray-500 hover:text-gray-700 text-xs font-medium">Edit</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                    <p>No bundle products found.</p>
                    <a href="{{ route('admin.products.create') }}" class="text-indigo-600 text-sm mt-2 inline-block">Create a product with type "Bundle"</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $bundles->links() }}</div>
</div>
@endsection
