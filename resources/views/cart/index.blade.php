@extends('layouts.app')
@section('title', 'Shopping Cart')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

    @if($cartItems->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
            <svg class="w-24 h-24 text-gray-200 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-3">Your cart is empty</h3>
            <p class="text-gray-500 mb-6">Browse products and add items to your cart</p>
            <a href="{{ route('shop.index') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition inline-block">
                Start Shopping
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-4">
                @foreach($cartItems as $item)
                    <div class="bg-white rounded-2xl shadow-sm p-6 flex items-center space-x-4">
                        <div class="w-24 h-24 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            @if($item->product->image)
                                <img src="{{ Storage::url($item->product->image) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('products.show', $item->product->slug) }}" class="font-semibold text-gray-800 hover:text-indigo-600 text-sm block truncate">
                                {{ $item->product->name }}
                            </a>
                            <p class="text-xs text-gray-400 mt-1">{{ $item->product->category->name }}</p>
                            <p class="text-indigo-600 font-bold mt-2">৳{{ number_format($item->product->effective_price) }}</p>
                        </div>
                        <div class="flex flex-col items-end space-y-3">
                            <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                @csrf @method('PATCH')
                                <button type="button" onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value)-1); this.form.submit();" class="px-3 py-1 text-gray-500 hover:bg-gray-100">-</button>
                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock }}"
                                    onchange="this.form.submit()" class="w-12 text-center text-sm border-0 focus:outline-none">
                                <button type="button" onclick="this.form.quantity.value = Math.min({{ $item->product->stock }}, parseInt(this.form.quantity.value)+1); this.form.submit();" class="px-3 py-1 text-gray-500 hover:bg-gray-100">+</button>
                            </form>
                            <div class="flex items-center space-x-3">
                                <span class="font-bold text-gray-900 text-sm">৳{{ number_format($item->subtotal) }}</span>
                                <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Order Summary -->
            <div class="space-y-4">
                <!-- Coupon -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Coupon Code</h3>
                    @if($coupon)
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-3">
                            <div>
                                <p class="text-sm font-semibold text-green-700">{{ $coupon }}</p>
                                <p class="text-xs text-green-500">Discount applied!</p>
                            </div>
                            <form action="{{ route('cart.coupon.remove') }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                            </form>
                        </div>
                    @else
                        <form action="{{ route('cart.coupon') }}" method="POST" class="flex space-x-2">
                            @csrf
                            <input type="text" name="code" placeholder="Enter coupon code"
                                class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Apply</button>
                        </form>
                    @endif
                </div>

                <!-- Summary -->
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Order Summary</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>৳{{ number_format($subtotal) }}</span>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-৳{{ number_format($discount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span>{{ $shipping > 0 ? '৳' . number_format($shipping) : 'Free' }}</span>
                        </div>
                        <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-900 text-base">
                            <span>Total</span>
                            <span>৳{{ number_format($total) }}</span>
                        </div>
                    </div>

                    @auth
                        <a href="{{ route('checkout.index') }}" class="block w-full mt-6 bg-indigo-600 text-white py-3 rounded-xl text-center font-semibold hover:bg-indigo-700 transition">
                            Proceed to Checkout
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full mt-6 bg-indigo-600 text-white py-3 rounded-xl text-center font-semibold hover:bg-indigo-700 transition">
                            Login to Checkout
                        </a>
                    @endauth

                    <a href="{{ route('shop.index') }}" class="block text-center text-sm text-gray-500 mt-3 hover:text-indigo-600">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
