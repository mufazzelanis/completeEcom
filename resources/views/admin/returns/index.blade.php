@extends('layouts.admin')
@section('title', 'Return Requests')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Return Requests</h1>
        <p class="text-sm text-gray-500 mt-1">Manage customer return and refund requests</p>
    </div>
</div>

@if($stats['pending'] > 0)
<div class="mb-4 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 flex items-center space-x-3">
    <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm text-orange-700 font-medium">{{ $stats['pending'] }} return request(s) awaiting review</p>
    <a href="{{ route('admin.returns.index', ['status' => 'pending']) }}" class="ml-auto text-xs text-orange-600 underline">View Pending</a>
</div>
@endif

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Pending Review</p>
        <p class="text-2xl font-bold text-orange-600">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Approved</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Total Returns</p>
        <p class="text-2xl font-bold text-indigo-600">{{ $stats['total'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Return no. / order no. / customer…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            @foreach(['pending','approved','rejected','completed'] as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.returns.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Return #</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Items</th>
                <th class="px-6 py-3 text-left">Refund Type</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($returns as $return)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3">
                    <a href="{{ route('admin.returns.show', $return) }}" class="text-indigo-600 hover:underline text-sm font-medium">
                        {{ $return->return_number }}
                    </a>
                </td>
                <td class="px-6 py-3">
                    <p class="text-sm font-medium text-gray-800">{{ $return->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $return->user->email }}</p>
                </td>
                <td class="px-6 py-3 text-sm text-gray-700">
                    {{ $return->order->order_number }}
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    {{ $return->items->count() }} item(s)
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">{{ $return->refundTypeLabel() }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $return->statusBadge() }}">
                        {{ $return->statusLabel() }}
                    </span>
                </td>
                <td class="px-6 py-3 text-center text-xs text-gray-500">{{ $return->created_at->format('d M Y') }}</td>
                <td class="px-6 py-3 text-right">
                    @if($return->status === 'pending')
                    <a href="{{ route('admin.returns.show', $return) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition">
                        Review
                    </a>
                    @else
                    <a href="{{ route('admin.returns.show', $return) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No return requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($returns->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $returns->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
