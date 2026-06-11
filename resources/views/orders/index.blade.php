@extends('layouts.app')
@section('title', 'My Orders')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">My Orders</h1>

    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
            <svg class="w-20 h-20 text-gray-200 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No orders yet</h3>
            <p class="text-gray-500 text-sm mb-6">Start shopping to create your first order</p>
            <a href="{{ route('shop.index') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition inline-block">Shop Now</a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Order Number</p>
                            <p class="font-bold text-gray-900">{{ $order->order_number }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Date</p>
                            <p class="text-sm text-gray-700">{{ $order->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-400 mb-1">Total</p>
                            <p class="font-bold text-gray-900">৳{{ number_format($order->total) }}</p>
                        </div>
                        <div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->status_badge }} capitalize">
                                {{ $order->status }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('orders.show', $order->id) }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">View Details</a>
                            @if(in_array($order->status, ['pending', 'processing']))
                                <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium"
                                        onclick="return confirm('Cancel this order?')">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 flex items-center space-x-3 overflow-x-auto">
                        @foreach($order->items->take(4) as $item)
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                @if($item->product && $item->product->image)
                                    <img src="{{ Storage::url($item->product->image) }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg"></div>
                                @endif
                                <div>
                                    <p class="text-xs text-gray-700 font-medium truncate max-w-24">{{ $item->product_name }}</p>
                                    <p class="text-xs text-gray-400">×{{ $item->quantity }}</p>
                                </div>
                            </div>
                        @endforeach
                        @if($order->items->count() > 4)
                            <span class="text-xs text-gray-400 flex-shrink-0">+{{ $order->items->count() - 4 }} more</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
