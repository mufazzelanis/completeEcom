@extends('layouts.admin')
@section('title', 'Support Tickets')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Support Tickets</h1>
        <p class="text-sm text-gray-500 mt-1">Answer customer support and help requests</p>
    </div>
</div>

@if($stats['open'] > 0)
<div class="mb-4 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 flex items-center space-x-3">
    <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm text-orange-700 font-medium">{{ $stats['open'] }} ticket(s) waiting for a reply</p>
    <a href="{{ route('admin.support-tickets.index', ['status' => 'open']) }}" class="ml-auto text-xs text-orange-600 underline">View Open</a>
</div>
@endif

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Open</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['open'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Pending (waiting on customer)</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Total Tickets</p>
        <p class="text-2xl font-bold text-indigo-600">{{ $stats['total'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ticket no. / subject / customer…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            @foreach(['open','pending','resolved','closed'] as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach(['order','payment','product','delivery','account','other'] as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
            @endforeach
        </select>
        <select name="priority" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Priorities</option>
            @foreach(['low','medium','high','urgent'] as $pr)
            <option value="{{ $pr }}" {{ request('priority') === $pr ? 'selected' : '' }}>{{ ucfirst($pr) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','status','category','priority']))
        <a href="{{ route('admin.support-tickets.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Ticket #</th>
                <th class="px-6 py-3 text-left">Subject</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-center">Priority</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Last Activity</th>
                <th class="px-6 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($tickets as $ticket)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3">
                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="text-indigo-600 hover:underline text-sm font-mono font-medium">
                        {{ $ticket->ticket_number }}
                    </a>
                </td>
                <td class="px-6 py-3 text-sm text-gray-700 max-w-xs truncate">{{ $ticket->subject }}</td>
                <td class="px-6 py-3">
                    <p class="text-sm font-medium text-gray-800">{{ $ticket->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $ticket->user->email }}</p>
                </td>
                <td class="px-6 py-3 text-sm text-gray-600 capitalize">{{ $ticket->category }}</td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->priority_badge }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $ticket->status_badge }}">
                        {{ ucfirst($ticket->status) }}
                    </span>
                </td>
                <td class="px-6 py-3 text-center text-xs text-gray-500">
                    {{ ($ticket->last_reply_at ?? $ticket->created_at)->diffForHumans() }}
                </td>
                <td class="px-6 py-3 text-right">
                    @if(in_array($ticket->status, ['open', 'pending']))
                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition">
                        Reply
                    </a>
                    @else
                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No support tickets found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($tickets->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $tickets->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
