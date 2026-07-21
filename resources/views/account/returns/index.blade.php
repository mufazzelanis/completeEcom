@extends('layouts.account')
@section('title', 'My Returns')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">My Returns</h1>
</div>

@if($returns->isEmpty())
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
    <p class="text-gray-500 text-sm mb-4">You haven't requested any returns yet.</p>
    <a href="{{ route('orders.index') }}" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">View My Orders</a>
</div>
@else
<div class="space-y-3">
    @foreach($returns as $return)
    <a href="{{ route('account.returns.show', $return) }}" class="block bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs text-gray-400">Return #</p>
                <p class="font-bold text-gray-900 text-sm">{{ $return->return_number }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">Order</p>
                <p class="text-sm text-gray-700">{{ $return->order->order_number }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">Requested</p>
                <p class="text-sm text-gray-700">{{ $return->created_at->format('M d, Y') }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">Items</p>
                <p class="text-sm font-medium text-gray-700">{{ $return->items->count() }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-gray-400">Requested Type</p>
                <p class="text-sm font-medium text-gray-700">{{ $return->refundTypeLabel() }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $return->statusBadge() }}">{{ $return->statusLabel() }}</span>
        </div>
    </a>
    @endforeach
</div>
<div class="mt-5">{{ $returns->links() }}</div>
@endif
@endsection
