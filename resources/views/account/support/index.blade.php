@extends('layouts.account')
@section('title', 'Support Tickets')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">Support Tickets</h1>
    <a href="{{ route('account.support.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Ticket
    </a>
</div>

@if($tickets->isEmpty())
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
    <p class="text-gray-500 text-sm mb-4">No support tickets yet.</p>
    <a href="{{ route('account.support.create') }}" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Open a Ticket</a>
</div>
@else
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100"><tr class="text-xs text-gray-500 uppercase">
            <th class="px-5 py-3 text-left">Subject</th>
            <th class="px-5 py-3 text-center">Category</th>
            <th class="px-5 py-3 text-center">Priority</th>
            <th class="px-5 py-3 text-center">Status</th>
            <th class="px-5 py-3 text-center">Replies</th>
            <th class="px-5 py-3 text-right">Date</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($tickets as $ticket)
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="location.href='{{ route('account.support.show', $ticket) }}'">
                <td class="px-5 py-3.5">
                    <p class="font-medium text-gray-800 hover:text-indigo-600">{{ $ticket->subject }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $ticket->ticket_number }}</p>
                </td>
                <td class="px-5 py-3.5 text-center">
                    <span class="text-xs text-gray-600 capitalize">{{ $ticket->category }}</span>
                </td>
                <td class="px-5 py-3.5 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $ticket->priority_badge }}">{{ ucfirst($ticket->priority) }}</span>
                </td>
                <td class="px-5 py-3.5 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $ticket->status_badge }}">{{ ucfirst($ticket->status) }}</span>
                </td>
                <td class="px-5 py-3.5 text-center text-xs text-gray-500">{{ $ticket->reply_count }}</td>
                <td class="px-5 py-3.5 text-right text-xs text-gray-400">{{ $ticket->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $tickets->links() }}</div>
</div>
@endif
@endsection
