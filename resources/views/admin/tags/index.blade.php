@extends('layouts.admin')
@section('title', 'Product Tags')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Product Tags</h1>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Add Tag</h3>
        <form action="{{ route('admin.tags.store') }}" method="POST" class="space-y-3">
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Tag name…"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Add Tag</button>
        </form>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
            <form method="GET" class="flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tags…"
                    class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700">Search</button>
                @if(request('search'))<a href="{{ route('admin.tags.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Tag</th>
                        <th class="px-6 py-3 text-left">Slug</th>
                        <th class="px-6 py-3 text-center">Products</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($tags as $tag)
                    <tr class="hover:bg-gray-50" x-data="{ editing: false, name: '{{ $tag->name }}' }">
                        <td class="px-6 py-3">
                            <div x-show="!editing" class="text-sm font-medium text-gray-800">{{ $tag->name }}</div>
                            <form x-show="editing" x-cloak action="{{ route('admin.tags.update', $tag) }}" method="POST" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <input type="text" name="name" x-model="name" class="border border-indigo-300 rounded-lg px-3 py-1 text-sm focus:outline-none w-36">
                                <button type="submit" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-lg">Save</button>
                                <button type="button" @click="editing=false" class="text-xs text-gray-400">✕</button>
                            </form>
                        </td>
                        <td class="px-6 py-3 text-xs font-mono text-gray-400">{{ $tag->slug }}</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $tag->products_count }}</td>
                        <td class="px-6 py-3 text-right flex items-center justify-end gap-3">
                            <button @click="editing=!editing" x-show="!editing" class="text-indigo-600 text-sm hover:text-indigo-800">Edit</button>
                            <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" onsubmit="return confirm('Delete tag?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 text-sm hover:text-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No tags yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($tags->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $tags->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
