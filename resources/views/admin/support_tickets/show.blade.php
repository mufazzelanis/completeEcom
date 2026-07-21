@extends('layouts.admin')
@section('title', 'Ticket: '.$ticket->ticket_number)

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.support-tickets.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <div class="flex-1 min-w-0">
        <h1 class="text-lg font-bold text-gray-900 truncate">{{ $ticket->subject }}</h1>
        <p class="text-xs text-gray-400 font-mono">{{ $ticket->ticket_number }}</p>
    </div>
    <span class="text-xs px-2.5 py-1 rounded-full font-semibold flex-shrink-0 {{ $ticket->status_badge }}">{{ ucfirst($ticket->status) }}</span>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        {{-- Messages --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="space-y-4">
                @foreach($ticket->messages as $msg)
                <div class="flex {{ $msg->is_staff ? 'flex-row-reverse' : '' }} items-start gap-3">
                    <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold {{ $msg->is_staff ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                        {{ $msg->is_staff ? 'S' : strtoupper(substr($msg->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 {{ $msg->is_staff ? 'text-right' : '' }}">
                        <div class="inline-block {{ $msg->is_staff ? 'bg-indigo-600 text-white' : 'bg-gray-50 border border-gray-100' }} rounded-2xl px-4 py-3 shadow-sm text-sm text-left max-w-lg">
                            <p class="{{ $msg->is_staff ? 'text-white' : 'text-gray-800' }}">{{ $msg->message }}</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $msg->is_staff ? 'Support Team' : ($msg->user->name ?? 'Customer') }} · {{ $msg->created_at->format('M d, Y H:i') }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Reply form --}}
        @if($ticket->status !== 'closed')
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <form action="{{ route('admin.support-tickets.reply', $ticket) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="message" rows="4" required minlength="1" maxlength="2000"
                    placeholder="Type your reply…"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('message') }}</textarea>
                @error('message')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Send Reply</button>
                    <span class="text-xs text-gray-400">Sending a reply marks this ticket as "Pending" (waiting on customer).</span>
                </div>
            </form>
        </div>
        @else
        <div class="bg-gray-50 rounded-2xl p-4 text-center text-sm text-gray-500">
            This ticket is closed.
        </div>
        @endif
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-5 text-sm space-y-2">
            <h3 class="font-semibold text-gray-800 mb-3">Ticket Info</h3>
            <div class="flex justify-between"><span class="text-gray-500">Number</span><span class="font-mono text-xs font-semibold text-gray-700">{{ $ticket->ticket_number }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Category</span><span class="capitalize text-gray-700">{{ $ticket->category }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Priority</span><span class="text-xs px-2 py-0.5 rounded-full {{ $ticket->priority_badge }} font-semibold">{{ ucfirst($ticket->priority) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Opened</span><span class="text-gray-700">{{ $ticket->created_at->format('M d, Y') }}</span></div>
            @if($ticket->order)
            <div class="flex justify-between"><span class="text-gray-500">Order</span><a href="{{ route('admin.orders.show', $ticket->order) }}" class="text-indigo-600 text-xs font-mono hover:text-indigo-800">{{ $ticket->order->order_number }}</a></div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5 text-sm space-y-2">
            <h3 class="font-semibold text-gray-800 mb-3">Customer</h3>
            <p class="font-medium text-gray-800">{{ $ticket->user->name }}</p>
            <p class="text-gray-500 text-xs">{{ $ticket->user->email }}</p>
            @if($ticket->user->phone)<p class="text-gray-500 text-xs">{{ $ticket->user->phone }}</p>@endif
            <a href="{{ route('admin.users.index', ['search' => $ticket->user->email]) }}" class="inline-block mt-2 text-indigo-600 hover:underline text-xs font-medium">View customer →</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-3 text-sm">Update Status</h3>
            <form action="{{ route('admin.support-tickets.status', $ticket) }}" method="POST" class="space-y-3">
                @csrf @method('PATCH')
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['open', 'pending', 'resolved', 'closed'] as $st)
                        <option value="{{ $st }}" {{ $ticket->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-xl text-sm font-medium hover:bg-gray-900 transition">Update Status</button>
            </form>
        </div>
    </div>
</div>
@endsection
