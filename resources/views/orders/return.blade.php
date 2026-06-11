@extends('layouts.app')
@section('title', 'Request Return — '.$order->order_number)

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
    <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Order</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-900 mb-1">Request Return</h1>
        <p class="text-sm text-gray-500 mb-6">Order <span class="font-medium text-gray-700">{{ $order->order_number }}</span></p>

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <form action="{{ route('orders.return.store', $order) }}" method="POST">
            @csrf

            {{-- Items --}}
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Select Items to Return</h3>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex items-center justify-between p-4 border border-gray-100 rounded-xl hover:border-indigo-200 transition">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">{{ $item->product_name }}</p>
                            <p class="text-xs text-gray-400">Ordered: {{ $item->quantity }} × ৳{{ number_format($item->price, 2) }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-xs text-gray-500">Qty to return</label>
                            <select name="items[{{ $item->id }}][qty]"
                                class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                <option value="0">0 (keep)</option>
                                @for($i = 1; $i <= $item->quantity; $i++)
                                <option value="{{ $i }}" {{ old("items.{$item->id}.qty") == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Refund type --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Preferred Resolution</label>
                <div class="grid grid-cols-3 gap-3" x-data="{ type: '{{ old('refund_type', 'refund') }}' }">
                    @foreach(['refund' => 'Cash Refund', 'exchange' => 'Exchange', 'store_credit' => 'Store Credit'] as $val => $label)
                    <label class="flex flex-col items-center p-3 border-2 rounded-xl cursor-pointer transition"
                           :class="type === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="refund_type" value="{{ $val }}" x-model="type" class="sr-only">
                        <span class="text-sm font-medium text-gray-800 mt-1">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Reason --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Reason for Return <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="4" required placeholder="Please describe the issue with your order…"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('reason') }}</textarea>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-xs text-gray-500">
                <p class="font-medium text-gray-600 mb-1">Return Policy</p>
                <p>Returns are accepted within 7 days of delivery. We'll review your request within 2–3 business days and contact you with next steps.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 py-3 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
                    Submit Return Request
                </button>
                <a href="{{ route('orders.show', $order) }}"
                    class="px-6 py-3 border border-gray-200 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
