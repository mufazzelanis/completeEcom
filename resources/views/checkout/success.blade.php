@extends('layouts.app')
@section('title', 'Order Placed!')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-2xl shadow-sm p-12">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-2">Thank you for your order.</p>
        <p class="text-orange-500 font-bold text-lg mb-6">{{ $order->order_number }}</p>

        @if($accountCreated ?? false)
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-green-700">Account Created!</p>
                        <p class="text-sm text-green-600">Your account has been created with phone number <strong>{{ auth()->user()->phone }}</strong>.</p>
                        <p class="text-xs text-green-500 mt-1">Next time, just login with your phone number to track orders and manage your account.</p>
                    </div>
                </div>
            </div>
        @endif

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
            <a href="{{ route('orders.show', $order->id) }}" class="bg-orange-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-600 transition">
                View Order Details
            </a>
            <a href="{{ route('shop.index') }}" class="border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
@endsection
