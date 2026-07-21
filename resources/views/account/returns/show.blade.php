@extends('layouts.account')
@section('title', 'Return '.$return->return_number)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('account.returns.index') }}" class="text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900">{{ $return->return_number }}</h1>
        <p class="text-xs text-gray-400">Requested {{ $return->created_at->format('M d, Y h:i A') }}</p>
    </div>
    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $return->statusBadge() }} ml-auto">{{ $return->statusLabel() }}</span>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Items</h2>
            <div class="space-y-4">
                @foreach($return->items as $item)
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            @if($item->product?->image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ $item->product_name }}</p>
                            <p class="text-gray-400 text-xs">Requested: {{ $item->quantity_requested }}</p>
                        </div>
                        @if($return->status === 'approved' || $return->status === 'completed')
                            <span class="text-xs font-semibold text-green-600">{{ $item->quantity_approved }} approved</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Your Reason</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $return->reason }}</p>
        </div>

        @if($return->admin_note)
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Note from {{ setting('site_name', 'Us') }}</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $return->admin_note }}</p>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Summary</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Order</span>
                    <a href="{{ route('orders.show', $return->order) }}" class="text-indigo-600 hover:underline font-medium">{{ $return->order->order_number }}</a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Requested type</span>
                    <span class="text-gray-800 font-medium">{{ $return->refundTypeLabel() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $return->statusBadge() }}">{{ $return->statusLabel() }}</span>
                </div>
                @if($return->processed_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Reviewed</span>
                    <span class="text-gray-800">{{ $return->processed_at->format('M d, Y') }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($return->status === 'pending')
        <div class="bg-yellow-50 border border-yellow-100 rounded-2xl p-5 text-sm text-yellow-700">
            We're reviewing your request and will respond within 2–3 business days.
        </div>
        @endif
    </div>
</div>
@endsection
