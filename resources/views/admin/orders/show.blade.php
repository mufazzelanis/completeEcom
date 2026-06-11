@extends('layouts.admin')
@section('title', 'Order Details')

@section('content')
<div class="flex items-center space-x-4 mb-6">
    <a href="{{ route('admin.orders.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back</span>
    </a>
    <h1 class="font-semibold text-gray-800">{{ $order->order_number }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            @if($item->product && $item->product->image)
                                <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-400">৳{{ number_format($item->price) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="font-bold text-gray-900">৳{{ number_format($item->subtotal) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Customer</h2>
                <p class="font-medium text-gray-800 text-sm">{{ $order->user->name }}</p>
                <p class="text-sm text-gray-500">{{ $order->user->email }}</p>
                <p class="text-sm text-gray-500">{{ $order->user->phone }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Shipping Address</h2>
                <div class="text-sm text-gray-600 space-y-1">
                    <p class="font-medium text-gray-800">{{ $order->shipping_name }}</p>
                    <p>{{ $order->shipping_phone }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->shipping_city }}, {{ $order->shipping_state }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <!-- Update Status -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Update Status</h2>
            <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                @csrf @method('PATCH')
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s)
                        <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Update Status</button>
            </form>
        </div>

        <!-- Update Payment -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Payment Status</h2>
            <form action="{{ route('admin.orders.update', $order->id) }}" method="POST">
                @csrf @method('PUT')
                <select name="payment_status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['pending', 'paid', 'failed', 'refunded'] as $s)
                        <option value="{{ $s }}" {{ $order->payment_status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Update Payment</button>
            </form>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Summary</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>৳{{ number_format($order->subtotal) }}</span></div>
                @if($order->discount > 0)
                    <div class="flex justify-between text-green-600"><span>Discount</span><span>-৳{{ number_format($order->discount) }}</span></div>
                @endif
                <div class="flex justify-between text-gray-600"><span>Shipping</span><span>৳{{ number_format($order->shipping) }}</span></div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                    <span>Total</span><span>৳{{ number_format($order->total) }}</span>
                </div>
                <div class="pt-2 flex justify-between text-sm text-gray-500">
                    <span>Payment Method</span>
                    <span class="capitalize">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
