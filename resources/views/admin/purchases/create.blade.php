@extends('layouts.admin')
@section('title', 'New Purchase Order')

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.purchases.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Purchases</span>
    </a>

    @if($errors->any())
    <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
        <ul class="text-sm text-red-600 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.purchases.store') }}" method="POST"
        x-data="{
            items: [{ product_id:'', quantity_ordered:1, unit_cost:'' }],
            products: {{ Js::from($products) }},
            addRow() { this.items.push({ product_id:'', quantity_ordered:1, unit_cost:'' }) },
            removeRow(i) { this.items.splice(i,1) },
            get total() { return this.items.reduce((s,r) => s + (parseFloat(r.quantity_ordered||0) * parseFloat(r.unit_cost||0)), 0) }
        }">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Items --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Purchase Items</h3>
                        <button type="button" @click="addRow()" class="flex items-center space-x-1 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Add Item</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium px-1 mb-2">
                        <div class="col-span-5">Product</div>
                        <div class="col-span-2">Qty</div>
                        <div class="col-span-3">Unit Cost (৳)</div>
                        <div class="col-span-1 text-right">Total</div>
                        <div class="col-span-1"></div>
                    </div>

                    <template x-for="(item, i) in items" :key="i">
                        <div class="grid grid-cols-12 gap-2 items-center mb-2">
                            <div class="col-span-5">
                                <select :name="`items[${i}][product_id]`" x-model="item.product_id"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select product…</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name + (p.sku ? ' ('+p.sku+')' : '')"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="number" :name="`items[${i}][quantity_ordered]`" x-model="item.quantity_ordered" min="1"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-3">
                                <input type="number" :name="`items[${i}][unit_cost]`" x-model="item.unit_cost" min="0" step="0.01" placeholder="0.00"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="col-span-1 text-right text-sm font-medium text-gray-700" x-text="'৳'+(parseFloat(item.quantity_ordered||0)*parseFloat(item.unit_cost||0)).toFixed(2)"></div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" @click="removeRow(i)" x-show="items.length > 1" class="text-red-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Order Total</p>
                            <p class="text-xl font-bold text-gray-900" x-text="'৳'+total.toFixed(2)"></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Notes</h3>
                    <textarea name="notes" rows="3" placeholder="Optional notes…"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Right sidebar --}}
            <div class="space-y-4">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Order Details</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference No</label>
                            <input type="text" value="{{ $reference }}" readonly
                                class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier *</label>
                            <select name="supplier_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('supplier_id') border-red-400 @enderror">
                                <option value="">Select supplier…</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}{{ $s->company ? ' — '.$s->company : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date *</label>
                            <input type="date" name="purchased_at" value="{{ old('purchased_at', date('Y-m-d')) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('purchased_at') border-red-400 @enderror">
                            @error('purchased_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="ordered" {{ old('status') == 'ordered' ? 'selected' : '' }}>Ordered</option>
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="received" {{ old('status') == 'received' ? 'selected' : '' }}>Received (auto-update stock)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid (৳)</label>
                            <input type="number" name="paid_amount" value="{{ old('paid_amount', 0) }}" min="0" step="0.01"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">
                    Create Purchase Order
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
