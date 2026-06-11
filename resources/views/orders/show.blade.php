@extends('layouts.app')
@section('title', 'Order Details')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-4 mb-8">
        <a href="{{ route('orders.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500">Placed on {{ $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $order->status_badge }} capitalize ml-auto">
            {{ $order->status }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                                @if($item->product && $item->product->image)
                                    <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm">{{ $item->product_name }}</p>
                                <p class="text-gray-400 text-xs">৳{{ number_format($item->price) }} × {{ $item->quantity }}</p>
                            </div>
                            <p class="font-bold text-gray-900">৳{{ number_format($item->subtotal) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Shipping Address</h2>
                <div class="text-sm text-gray-600 space-y-1">
                    <p class="font-semibold text-gray-800">{{ $order->shipping_name }}</p>
                    <p>{{ $order->shipping_phone }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                    <p>{{ $order->shipping_country }}</p>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Payment Summary</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>৳{{ number_format($order->subtotal) }}</span></div>
                    @if($order->discount > 0)
                        <div class="flex justify-between text-green-600"><span>Discount</span><span>-৳{{ number_format($order->discount) }}</span></div>
                    @endif
                    <div class="flex justify-between text-gray-600"><span>Shipping</span><span>৳{{ number_format($order->shipping) }}</span></div>
                    <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                        <span>Total</span><span>৳{{ number_format($order->total) }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Payment</span>
                        <span class="font-medium capitalize">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-500">Payment Status</span>
                        <span class="capitalize font-medium {{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-yellow-600' }}">{{ $order->payment_status }}</span>
                    </div>
                </div>
            </div>

            @if(in_array($order->status, ['pending', 'processing']))
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit" onclick="return confirm('Cancel this order?')"
                        class="w-full border border-red-300 text-red-600 py-2.5 rounded-xl text-sm font-medium hover:bg-red-50 transition">
                        Cancel Order
                    </button>
                </form>
            @endif

            @if(in_array($order->status, ['delivered', 'completed']))
                @if($existingReturn)
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1">Return Request</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $existingReturn->statusBadge() }}">
                            {{ $existingReturn->statusLabel() }}
                        </span>
                        <p class="text-xs text-gray-400 mt-1">{{ $existingReturn->return_number }}</p>
                    </div>
                @else
                    <a href="{{ route('orders.return.create', $order) }}"
                        class="flex items-center justify-center w-full border border-gray-300 text-gray-700 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        Request Return
                    </a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
