@extends('layouts.app')
@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="max-w-[900px] mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900">Order Details</h1>
            <p class="text-gray-400 text-sm mt-1">{{ $order->order_number }}</p>
        </div>
        <span class="px-4 py-1.5 rounded-full text-sm font-bold {{ $order->status_badge }}">{{ ucfirst($order->status) }}</span>
    </div>

    {{-- Order Status Timeline --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="font-bold text-gray-800 mb-4">Order Status</h2>
        <div class="flex items-center justify-between">
            @php
                $steps = ['pending' => 'Order Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
                $currentIndex = array_search($order->status, array_keys($steps));
                if ($order->status === 'cancelled') $currentIndex = 0;
            @endphp
            @foreach($steps as $key => $label)
                <div class="flex-1 flex flex-col items-center {{ !$loop->last ? 'relative' : '' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ array_search($key, array_keys($steps)) <= $currentIndex ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if(array_search($key, array_keys($steps)) < $currentIndex)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ array_search($key, array_keys($steps)) + 1 }}
                        @endif
                    </div>
                    <p class="text-[10px] mt-1.5 text-center font-medium {{ array_search($key, array_keys($steps)) <= $currentIndex ? 'text-orange-600' : 'text-gray-400' }}">{{ $label }}</p>
                    @if(!$loop->last)
                        <div class="absolute top-4 left-[55%] right-[-45%] h-0.5 {{ array_search($key, array_keys($steps)) < $currentIndex ? 'bg-orange-500' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($order->status === 'cancelled')
            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-600 font-medium">This order has been cancelled.</div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Shipping --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 mb-3">Shipping Details</h2>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-500">Name:</span> <span class="font-medium">{{ $order->shipping_name }}</span></p>
                <p><span class="text-gray-500">Phone:</span> <span class="font-medium">{{ $order->shipping_phone }}</span></p>
                <p><span class="text-gray-500">Address:</span> <span class="font-medium">{{ $order->shipping_address }}, {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }}{{ $order->shipping_zip ? ' - ' . $order->shipping_zip : '' }}</span></p>
                @if($order->guest_email)
                    <p><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $order->guest_email }}</span></p>
                @endif
            </div>
        </div>

        {{-- Payment --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="font-bold text-gray-800 mb-3">Payment</h2>
            <div class="space-y-2 text-sm">
                <p><span class="text-gray-500">Method:</span> <span class="font-medium capitalize">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</span></p>
                <p><span class="text-gray-500">Status:</span> <span class="font-medium capitalize">{{ str_replace('_', ' ', $order->payment_status) }}</span></p>
                <div class="border-t border-gray-100 pt-2 mt-2 space-y-1">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span>৳{{ number_format($order->subtotal) }}</span></div>
                    @if($order->discount > 0)<div class="flex justify-between text-sm text-green-600"><span>Discount</span><span>-৳{{ number_format($order->discount) }}</span></div>@endif
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Shipping</span><span>৳{{ number_format($order->shipping) }}</span></div>
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-1"><span>Total</span><span>৳{{ number_format($order->total) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
        <h2 class="font-bold text-gray-800 mb-4">Order Items</h2>
        <div class="space-y-3">
            @foreach($order->items as $item)
                <div class="flex items-center gap-4 py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        @if($item->product && $item->product->image)
                            <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $item->product_name }}</p>
                        <p class="text-xs text-gray-400">Qty: {{ $item->quantity }} × ৳{{ number_format($item->price) }}</p>
                    </div>
                    <p class="text-sm font-bold text-gray-900">৳{{ number_format($item->subtotal) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="text-center mt-8">
        <a href="{{ route('shop.index') }}" class="bg-orange-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-orange-600 transition inline-block">Continue Shopping</a>
    </div>
</div>
@endsection
