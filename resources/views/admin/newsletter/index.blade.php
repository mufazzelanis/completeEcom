@extends('layouts.admin')
@section('title', 'Newsletter Subscribers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $activeCount }} active subscriber(s)</p>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request('search') || request('status'))<a href="{{ route('admin.newsletter.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Email</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-left">Subscribed</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($subscribers as $subscriber)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-800">{{ $subscriber->email }}</td>
                <td class="px-6 py-4 text-center">
                    @if($subscriber->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Unsubscribed</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $subscriber->subscribed_at?->format('M d, Y') ?? '—' }}</td>
                <td class="px-6 py-4 text-right">
                    <form action="{{ route('admin.newsletter.destroy', $subscriber) }}" method="POST" onsubmit="return confirm('Remove this subscriber?')">
                        @csrf @method('DELETE')
                        <button class="text-red-500 hover:text-red-700 text-sm font-medium">Remove</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400 text-sm">No subscribers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($subscribers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $subscribers->links() }}</div>
    @endif
</div>
@endsection
