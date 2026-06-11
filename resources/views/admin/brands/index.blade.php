@extends('layouts.admin')
@section('title', 'Brands')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Brands</h1>
    <a href="{{ route('admin.brands.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">+ Add Brand</a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brands…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Search</button>
        @if(request('search'))<a href="{{ route('admin.brands.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Brand</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($brands as $brand)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3">
                    <div class="flex items-center space-x-3">
                        @if($brand->logo)
                        <img src="{{ asset('storage/'.$brand->logo) }}" class="w-10 h-10 rounded-lg object-contain bg-gray-50 border border-gray-100">
                        @else
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 font-bold text-sm">{{ strtoupper(substr($brand->name,0,1)) }}</div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $brand->name }}</p>
                            @if($brand->website)<a href="{{ $brand->website }}" target="_blank" class="text-xs text-indigo-500 hover:underline">{{ parse_url($brand->website, PHP_URL_HOST) }}</a>@endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-700">{{ $brand->products_count }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $brand->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $brand->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-3 text-right">
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('admin.brands.edit', $brand) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('Delete this brand?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No brands yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($brands->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $brands->links() }}</div>@endif
</div>
@endsection
