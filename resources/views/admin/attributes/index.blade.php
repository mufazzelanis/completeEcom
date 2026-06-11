@extends('layouts.admin')
@section('title', 'Product Attributes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Product Attributes</h1>
        <p class="text-sm text-gray-500 mt-1">Global attribute names used as specs (e.g. Material, Weight, Fit)</p>
    </div>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Add form --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Add Attribute</h3>
        <form action="{{ route('admin.attributes.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Attribute Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Material, Weight, Fit"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Add Attribute</button>
        </form>
    </div>

    {{-- List --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
            <form method="GET" class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search…"
                    class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Search</button>
                @if(request('search'))<a href="{{ route('admin.attributes.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Name</th>
                        <th class="px-6 py-3 text-center">Sort</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($attributes as $attr)
                    <tr class="hover:bg-gray-50 transition" x-data="{ editing: false, name: '{{ $attr->name }}', sort: {{ $attr->sort_order }} }">
                        <td class="px-6 py-3">
                            <div x-show="!editing" class="text-sm font-medium text-gray-800" x-text="name"></div>
                            <form x-show="editing" x-cloak action="{{ route('admin.attributes.update', $attr) }}" method="POST" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" x-model="name" class="border border-indigo-300 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 w-40">
                                <input type="number" name="sort_order" x-model="sort" class="border border-gray-200 rounded-lg px-2 py-1 text-sm w-16 focus:outline-none" min="0">
                                <button type="submit" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700">Save</button>
                                <button type="button" @click="editing=false" class="text-xs text-gray-400">✕</button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-center text-sm text-gray-500">{{ $attr->sort_order }}</td>
                        <td class="px-6 py-3 text-center">
                            <form action="{{ route('admin.attributes.toggle', $attr) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $attr->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $attr->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button type="button" @click="editing=!editing" x-show="!editing" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                                <form action="{{ route('admin.attributes.destroy', $attr) }}" method="POST" onsubmit="return confirm('Delete this attribute?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No attributes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($attributes->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $attributes->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
