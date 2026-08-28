@extends('layouts.admin')
@section('title', 'Landing Pages')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-sm text-gray-500">{{ $landingPages->total() }} landing page(s)</p>
    </div>
    <a href="{{ route('admin.landing-pages.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Landing Page
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Status</label>
            <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
            <a href="{{ route('admin.landing-pages.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Landing Page</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-center">Orders</th>
                <th class="px-6 py-3 text-center">Views</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($landingPages as $lp)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="font-medium text-gray-800 text-sm">{{ $lp->title }}</p>
                    <a href="{{ url($lp->slug) }}" target="_blank" class="text-xs text-indigo-500 hover:text-indigo-700 hover:underline">
                        {{ request()->getHost() }}/{{ $lp->slug }} ↗
                    </a>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $lp->product->name ?? '—' }}</td>
                <td class="px-6 py-4 text-center text-sm font-medium text-gray-700">{{ $lp->orders_count }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $lp->views_count }}</td>
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('admin.landing-pages.toggle', $lp) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium transition {{ $lp->status === 'published' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            {{ ucfirst($lp->status) }}
                        </button>
                    </form>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ route('admin.landing-pages.edit', $lp) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.landing-pages.destroy', $lp) }}" method="POST" onsubmit="return confirm('Delete this landing page? Orders already placed through it are kept.')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">
                No landing pages yet. <a href="{{ route('admin.landing-pages.create') }}" class="text-indigo-600">Create one</a>.
            </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $landingPages->links() }}</div>
</div>
@endsection
