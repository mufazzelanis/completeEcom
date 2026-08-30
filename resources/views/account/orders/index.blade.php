@extends('layouts.account')
@section('title', 'My Orders')
@section('pageTitle', 'My Orders')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">My Orders</h1>
    <div class="flex gap-1.5 overflow-x-auto pb-1 -mb-1">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $val => $label)
        <a href="{{ route('orders.index', $val !== 'all' ? ['status' => $val] : []) }}"
            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition whitespace-nowrap {{ (request('status', 'all') === $val) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

@if($orders->isEmpty())
<div class="bg-white rounded-2xl shadow-sm p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
    <p class="text-gray-500 text-sm mb-4">No orders found.</p>
    <a href="{{ route('shop.index') }}" class="inline-block bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Start Shopping</a>
</div>
@else
<div class="space-y-3">
    @foreach($orders as $order)
    <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">
        {{-- Row 1: Order info --}}
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
                <p class="text-xs text-gray-400">Order #</p>
                <p class="font-bold text-gray-900 text-sm">{{ $order->order_number }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->status_badge }} capitalize flex-shrink-0">{{ $order->status }}</span>
        </div>

        {{-- Row 2: Details grid --}}
        <div class="grid grid-cols-3 gap-3 mb-3 text-center">
            <div>
                <p class="text-xs text-gray-400">Date</p>
                <p class="text-sm text-gray-700">{{ $order->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Items</p>
                <p class="text-sm font-medium text-gray-700">{{ $order->items->count() }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Total</p>
                <p class="font-bold text-gray-900 text-sm">৳{{ number_format($order->total) }}</p>
            </div>
        </div>

        {{-- Row 3: Items preview --}}
        <div class="flex items-center gap-2 overflow-x-auto border-t border-gray-100 pt-3">
            @foreach($order->items->take(5) as $item)
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden p-1">
                    @if($item->product?->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item->product->image) }}" class="w-full h-full object-contain">
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-700 font-medium max-w-20 truncate">{{ $item->product_name }}</p>
                    <p class="text-xs text-gray-400">×{{ $item->quantity }}</p>
                </div>
            </div>
            @endforeach
            @if($order->items->count() > 5)
            <span class="text-xs text-gray-400 flex-shrink-0">+{{ $order->items->count() - 5 }} more</span>
            @endif
        </div>

        {{-- Row 4: Actions --}}
        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100">
            <a href="{{ route('orders.show', $order) }}" class="flex-1 text-center bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition text-xs font-semibold px-3 py-2 rounded-xl">View Details</a>
            @if(in_array($order->status, ['pending','processing']))
            <form action="{{ route('orders.cancel', $order) }}" method="POST">
                @csrf
                <button class="text-xs text-red-500 hover:text-red-700 font-medium px-3 py-2" onclick="return confirm('Cancel this order?')">Cancel</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
<div class="mt-5">{{ $orders->links() }}</div>
@endif
@endsection
