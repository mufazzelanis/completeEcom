@extends('layouts.app')
@section('title', 'Track Your Order')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16">
    <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Track Your Order</h1>
        <p class="text-gray-500 text-sm mb-8">Enter your order number to track your order status.</p>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-left">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('guest.order.track.lookup') }}" method="POST" class="space-y-4 text-left">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                <input type="text" name="order_number" value="{{ old('order_number') }}"
                    placeholder="e.g. ORD-XXXXXXXX"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-gray-400">(optional)</span></label>
                <input type="email" name="shipping_email" value="{{ old('shipping_email') }}"
                    placeholder="you@example.com"
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-lg font-bold hover:bg-orange-600 transition">
                Track Order
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-100">
            <a href="{{ route('home') }}" class="text-orange-500 hover:text-orange-700 text-sm font-medium">← Back to Home</a>
        </div>
    </div>
</div>
@endsection
