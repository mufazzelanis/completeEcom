@extends('layouts.admin')
@section('title', 'Return — '.$return->return_number)

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.returns.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Returns</span>
    </a>

    @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ $errors->first() }}</div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: return details --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Status card --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $return->return_number }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Requested {{ $return->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $return->statusBadge() }}">{{ $return->statusLabel() }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Refund Type</p>
                        <p class="font-medium text-gray-900">{{ $return->refundTypeLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs mb-1">Order</p>
                        <p class="font-medium text-gray-900">{{ $return->order->order_number }}</p>
                    </div>
                </div>
                <div class="mt-4 bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-500 mb-1">Customer Reason</p>
                    <p class="text-sm text-gray-700">{{ $return->reason }}</p>
                </div>
                @if($return->admin_note)
                <div class="mt-3 bg-blue-50 rounded-xl p-3">
                    <p class="text-xs text-gray-500 mb-1">Admin Note</p>
                    <p class="text-sm text-gray-700">{{ $return->admin_note }}</p>
                    @if($return->processedBy)
                    <p class="text-xs text-gray-400 mt-1">— {{ $return->processedBy->name }}, {{ $return->processed_at?->format('d M Y H:i') }}</p>
                    @endif
                </div>
                @endif
            </div>

            {{-- Return items --}}
            @if($return->status === 'pending')
            <form action="{{ route('admin.returns.approve', $return) }}" method="POST" id="approveForm">
            @csrf
            @endif

            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Requested Items</h3>
                    @if($return->status === 'pending')
                    <p class="text-xs text-gray-400 mt-0.5">Set approved quantity for each item (0 = not approved)</p>
                    @endif
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left">Product</th>
                            <th class="px-6 py-3 text-center">Requested</th>
                            @if($return->status === 'pending')
                            <th class="px-6 py-3 text-center">Approve Qty</th>
                            @else
                            <th class="px-6 py-3 text-center">Approved</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($return->items as $item)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-800">
                                {{ $item->product_name }}
                                @if($item->product)
                                <p class="text-xs text-gray-400">Current stock: {{ $item->product->stock }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $item->quantity_requested }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($return->status === 'pending')
                                <input type="number" name="approved_qty[{{ $item->id }}]"
                                    min="0" max="{{ $item->quantity_requested }}" value="{{ $item->quantity_requested }}"
                                    class="w-20 text-center border border-gray-200 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                @else
                                <span class="text-sm font-medium {{ $item->quantity_approved > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $item->quantity_approved }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($return->status === 'pending')
            </form>
            @endif

        </div>

        {{-- Right: actions + customer --}}
        <div class="space-y-4">

            @if($return->status === 'pending')
            {{-- Approve --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Approve Return</h3>
                <p class="text-xs text-gray-500 mb-3">Stock will be automatically updated for approved quantities.</p>
                <textarea name="admin_note" form="approveForm" placeholder="Optional note to customer…" rows="2"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                <button type="submit" form="approveForm"
                    class="w-full py-2.5 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition">
                    ✓ Approve & Restock
                </button>
            </div>

            {{-- Reject --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Reject Return</h3>
                <form action="{{ route('admin.returns.reject', $return) }}" method="POST"
                      onsubmit="return confirm('Reject this return request?')">
                    @csrf
                    <textarea name="admin_note" placeholder="Reason for rejection (required)" rows="2" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                    <button type="submit" class="w-full py-2.5 border border-red-300 text-red-600 rounded-xl text-sm font-medium hover:bg-red-50 transition">
                        ✕ Reject Request
                    </button>
                </form>
            </div>
            @endif

            {{-- Customer info --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3 text-sm">
                <h3 class="font-semibold text-gray-800">Customer</h3>
                <p class="font-medium text-gray-900">{{ $return->user->name }}</p>
                <p class="text-gray-500">{{ $return->user->email }}</p>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500">Original Order</p>
                    <p class="font-medium text-gray-800 mt-1">{{ $return->order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $return->order->created_at->format('d M Y') }}</p>
                    <p class="text-xs font-semibold text-gray-700 mt-1">৳{{ number_format($return->order->total, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
