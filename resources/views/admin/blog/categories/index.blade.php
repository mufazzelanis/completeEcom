@extends('layouts.admin')
@section('title', 'Blog Categories')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $categories->count() }} categories</p>
    <a href="{{ route('admin.blog.categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Category
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-left">Parent</th>
                <th class="px-6 py-3 text-center">Posts</th>
                <th class="px-6 py-3 text-center">Order</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($categories as $cat)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        @if($cat->image)
                            <img src="{{ Storage::url($cat->image) }}" class="w-10 h-10 rounded-lg object-cover">
                        @else
                            <div class="w-10 h-10 bg-indigo-50 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $cat->name }}</p>
                            <p class="text-xs text-gray-400">{{ $cat->slug }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $cat->parent?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-center text-sm font-semibold text-gray-700">{{ $cat->posts_count }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $cat->sort_order }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $cat->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $cat->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.blog.categories.edit', $cat) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.blog.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?{{ $cat->posts_count > 0 ? ' '.$cat->posts_count.' post(s) will become uncategorized.' : '' }}')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No categories yet. <a href="{{ route('admin.blog.categories.create') }}" class="text-indigo-600">Add one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
