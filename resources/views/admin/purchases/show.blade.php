@extends('layouts.admin')
@section('title', 'Purchase — '.$purchase->reference_no)

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.purchases.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Purchases</span>
        </a>
        <div class="flex space-x-2">
            @if(!in_array($purchase->status, ['received','cancelled']))
            <a href="{{ route('admin.purchases.edit', $purchase) }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium hover:bg-gray-50 transition">Edit</a>
            @endif
            @if(in_array($purchase->status, ['ordered','partial']))
            <button onclick="document.getElementById('receive-modal').classList.remove('hidden')"
                class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition">
                Mark as Received
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Items table --}}
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Items</h3>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3 text-left">Product</th>
                            <th class="px-6 py-3 text-center">Ordered</th>
                            <th class="px-6 py-3 text-center">Received</th>
                            <th class="px-6 py-3 text-right">Unit Cost</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($purchase->items as $item)
                        <tr>
                            <td class="px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ $item->product->name }}</p>
                                @if($item->product->sku)<p class="text-xs text-gray-400">{{ $item->product->sku }}</p>@endif
                            </td>
                            <td class="px-6 py-3 text-center text-sm text-gray-700">{{ $item->quantity_ordered }}</td>
                            <td class="px-6 py-3 text-center text-sm {{ $item->quantity_received >= $item->quantity_ordered ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                                {{ $item->quantity_received }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm text-gray-700">৳{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-6 py-3 text-right text-sm font-medium text-gray-800">৳{{ number_format($item->total_cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Total Amount</td>
                            <td class="px-6 py-3 text-right text-base font-bold text-gray-900">৳{{ number_format($purchase->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-2 text-right text-sm text-gray-500">Amount Paid</td>
                            <td class="px-6 py-2 text-right text-sm text-green-600 font-medium">৳{{ number_format($purchase->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-2 text-right text-sm text-gray-500">Balance Due</td>
                            <td class="px-6 py-2 text-right text-sm font-medium {{ ($purchase->total_amount - $purchase->paid_amount) > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                ৳{{ number_format($purchase->total_amount - $purchase->paid_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($purchase->notes)
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-2">Notes</h3>
                <p class="text-sm text-gray-600">{{ $purchase->notes }}</p>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3">
                <h3 class="font-semibold text-gray-800 mb-3">Order Info</h3>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Reference</span>
                    <span class="font-mono font-medium text-gray-800">{{ $purchase->reference_no }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $purchase->statusBadge() }}">{{ ucfirst($purchase->status) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Purchase Date</span>
                    <span class="text-gray-800">{{ $purchase->purchased_at->format('d M Y') }}</span>
                </div>
                @if($purchase->received_at)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Received</span>
                    <span class="text-gray-800">{{ $purchase->received_at->format('d M Y') }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Created By</span>
                    <span class="text-gray-800">{{ $purchase->creator->name }}</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3">
                <h3 class="font-semibold text-gray-800 mb-3">Supplier</h3>
                <p class="font-medium text-gray-900 text-sm">{{ $purchase->supplier->name }}</p>
                @if($purchase->supplier->company)<p class="text-xs text-gray-500">{{ $purchase->supplier->company }}</p>@endif
                @if($purchase->supplier->phone)<p class="text-xs text-gray-500 mt-1">{{ $purchase->supplier->phone }}</p>@endif
                @if($purchase->supplier->email)<p class="text-xs text-gray-500">{{ $purchase->supplier->email }}</p>@endif
            </div>

            @if(!in_array($purchase->status, ['received','partial','cancelled']))
            <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST"
                onsubmit="return confirm('Delete this purchase order?')">
                @csrf @method('DELETE')
                <button class="w-full px-4 py-2.5 border border-red-200 text-red-600 rounded-xl text-sm font-medium hover:bg-red-50 transition">
                    Delete Purchase
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- Receive modal --}}
<div id="receive-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Receive Stock</h3>
        <p class="text-sm text-gray-500 mb-4">Enter quantities actually received. Stock will be updated immediately.</p>
        <form action="{{ route('admin.purchases.receive', $purchase) }}" method="POST">
            @csrf
            <table class="w-full mb-4">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="text-left pb-2">Product</th>
                        <th class="text-center pb-2">Ordered</th>
                        <th class="text-center pb-2">Receive</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($purchase->items as $item)
                    <tr>
                        <td class="py-2 text-sm text-gray-800">{{ $item->product->name }}</td>
                        <td class="py-2 text-center text-sm text-gray-500">{{ $item->quantity_ordered }}</td>
                        <td class="py-2 text-center">
                            <input type="number" name="quantities[{{ $item->id }}]"
                                value="{{ $item->quantity_ordered }}" min="0" max="{{ $item->quantity_ordered }}"
                                class="w-20 border border-gray-200 rounded-lg px-2 py-1 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('receive-modal').classList.add('hidden')"
                    class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition">Confirm Receipt & Update Stock</button>
            </div>
        </form>
    </div>
</div>
@endsection
