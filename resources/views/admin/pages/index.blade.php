@extends('layouts.admin')
@section('title', 'Pages')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $pages->total() }} pages</p>
    <a href="{{ route('admin.pages.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Page
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Page</th>
                <th class="px-6 py-3 text-center">Type</th>
                <th class="px-6 py-3 text-center">Template</th>
                <th class="px-6 py-3 text-center">Order</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($pages as $page)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="font-medium text-gray-800 text-sm">{{ $page->title }}</p>
                    <p class="text-xs text-gray-400">/{{ $page->slug }}</p>
                </td>
                <td class="px-6 py-4 text-center">
                    @php $tc = match($page->type) { 'landing' => 'bg-orange-100 text-orange-700', 'seo' => 'bg-blue-100 text-blue-700', default => 'bg-gray-100 text-gray-600' }; @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tc }}">{{ ucfirst($page->type) }}</span>
                </td>
                <td class="px-6 py-4 text-center text-xs text-gray-500">{{ $page->template ?? 'default' }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $page->sort_order }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $page->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $page->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete this page?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No pages yet. <a href="{{ route('admin.pages.create') }}" class="text-indigo-600">Create one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $pages->links() }}</div>
</div>
@endsection
