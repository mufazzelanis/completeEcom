@extends('layouts.app')
@section('title', 'Order Placed!')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-3xl shadow-sm p-12">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-2">Thank you for your order.</p>
        <p class="text-indigo-600 font-semibold text-lg mb-6">{{ $order->order_number }}</p>

        <div class="bg-gray-50 rounded-xl p-6 text-left mb-8">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Payment Method</p>
                    <p class="font-semibold capitalize text-gray-800">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Order Total</p>
                    <p class="font-semibold text-gray-800">৳{{ number_format($order->total) }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-500">Shipping to</p>
                    <p class="font-semibold text-gray-800">{{ $order->shipping_name }}, {{ $order->shipping_address }}, {{ $order->shipping_city }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 justify-center">
            <a href="{{ route('orders.show', $order->id) }}" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">
                View Order Details
            </a>
            <a href="{{ route('shop.index') }}" class="border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection
