@extends('layouts.account')
@section('title', 'Ticket: '.$ticket->ticket_number)

@section('content')
<div class="flex items-center gap-4 mb-5">
    <a href="{{ route('account.support.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-lg font-bold text-gray-800 flex-1 min-w-0 truncate">{{ $ticket->subject }}</h1>
    <span class="text-xs px-2 py-0.5 rounded-full font-semibold flex-shrink-0 {{ $ticket->status_badge }}">{{ ucfirst($ticket->status) }}</span>
</div>

<div class="grid grid-cols-3 gap-5">
    <div class="col-span-2">
        {{-- Messages --}}
        <div class="space-y-4 mb-5">
            @foreach($ticket->messages as $msg)
            <div class="flex {{ $msg->is_staff ? '' : 'flex-row-reverse' }} items-start gap-3">
                <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold {{ $msg->is_staff ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">
                    {{ $msg->is_staff ? 'S' : strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 {{ $msg->is_staff ? '' : 'text-right' }}">
                    <div class="inline-block {{ $msg->is_staff ? 'bg-white border border-gray-100' : 'bg-indigo-600 text-white' }} rounded-2xl px-4 py-3 shadow-sm text-sm text-left max-w-lg">
                        <p class="{{ $msg->is_staff ? 'text-gray-800' : 'text-white' }}">{{ $msg->message }}</p>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $msg->is_staff ? 'Support Team' : 'You' }} · {{ $msg->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Reply form --}}
        @if(in_array($ticket->status, ['open', 'pending']))
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <form action="{{ route('account.support.reply', $ticket) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="message" rows="4" required minlength="5" maxlength="2000"
                    placeholder="Type your reply..."
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                @error('message')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Send Reply</button>
                    <form action="{{ route('account.support.close', $ticket) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm font-medium" onclick="return confirm('Close this ticket?')">Close Ticket</button>
                    </form>
                </div>
            </form>
        </div>
        @else
        <div class="bg-gray-50 rounded-2xl p-4 text-center text-sm text-gray-500">
            This ticket is {{ $ticket->status }}. <a href="{{ route('account.support.create') }}" class="text-indigo-600">Open a new ticket</a> if you need further help.
        </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-5 text-sm space-y-2">
            <h3 class="font-semibold text-gray-800 mb-3">Ticket Info</h3>
            <div class="flex justify-between"><span class="text-gray-500">Number</span><span class="font-mono text-xs font-semibold text-gray-700">{{ $ticket->ticket_number }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Category</span><span class="capitalize text-gray-700">{{ $ticket->category }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Priority</span><span class="text-xs px-2 py-0.5 rounded-full {{ $ticket->priority_badge }} font-semibold">{{ ucfirst($ticket->priority) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Opened</span><span class="text-gray-700">{{ $ticket->created_at->format('M d, Y') }}</span></div>
            @if($ticket->order)
            <div class="flex justify-between"><span class="text-gray-500">Order</span><a href="{{ route('orders.show', $ticket->order) }}" class="text-indigo-600 text-xs font-mono hover:text-indigo-800">{{ $ticket->order->order_number }}</a></div>
            @endif
        </div>
    </div>
</div>
@endsection
