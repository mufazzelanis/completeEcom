@extends('layouts.admin')
@section('title', 'Subcategories')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage subcategories grouped under parent categories</p>
    <a href="{{ route('admin.subcategories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Subcategory</span>
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Filter bar --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4 flex flex-wrap gap-3">
    <form method="GET" class="flex flex-wrap gap-3 w-full">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subcategories…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="parent" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Parents</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ request('parent') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','parent']))
            <a href="{{ route('admin.subcategories.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-left">Parent Category</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Sort</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($subcategories as $sub)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        @if($sub->image)
                            <img src="{{ Storage::url($sub->image) }}" class="w-9 h-9 rounded-lg object-cover">
                        @else
                            <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-400 text-xs font-bold">{{ strtoupper(substr($sub->name,0,2)) }}</div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $sub->name }}</p>
                            <p class="text-xs text-gray-400">{{ $sub->slug }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $sub->parent->name ?? '—' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $sub->products_count }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $sub->sort_order }}</td>
                <td class="px-6 py-4 text-center">
                    @if($sub->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.subcategories.edit', $sub->id) }}"
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.subcategories.destroy', $sub->id) }}" method="POST"
                            onsubmit="return confirm('Delete this subcategory?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No subcategories found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($subcategories->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $subcategories->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
