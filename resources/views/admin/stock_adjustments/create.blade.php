@extends('layouts.admin')
@section('title', 'New Stock Adjustment')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.stock-adjustments.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Adjustments</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4">
            <ul class="text-sm text-red-600 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form action="{{ route('admin.stock-adjustments.store') }}" method="POST"
            x-data="{
                type: '{{ old('type', $selectedType) }}',
                productId: '{{ old('product_id') }}',
                products: {{ Js::from($products) }},
                get currentStock() {
                    const p = this.products.find(p => p.id == this.productId);
                    return p ? p.stock : null;
                },
                get isOut() { return ['damage_out','manual_out'].includes(this.type) }
            }">
            @csrf
            <div class="space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adjustment Type *</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            'return_in'  => ['Customer Return', 'green', 'Stock returned by customer'],
                            'manual_in'  => ['Manual Stock In', 'green', 'Add stock manually'],
                            'damage_out' => ['Damage / Loss', 'red', 'Remove damaged/lost stock'],
                            'manual_out' => ['Manual Stock Out', 'red', 'Remove stock manually'],
                        ] as $val => [$label, $color, $hint])
                        <label class="relative flex items-start cursor-pointer border rounded-xl p-3 transition"
                            :class="type === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" name="type" value="{{ $val }}" x-model="type" class="sr-only">
                            <div>
                                <p class="text-sm font-medium {{ $color === 'green' ? 'text-green-700' : 'text-red-600' }}">{{ $label }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $hint }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                    <select name="product_id" x-model="productId"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('product_id') border-red-400 @enderror">
                        <option value="">Select product…</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}{{ $p->sku ? ' ('.$p->sku.')' : '' }} — Stock: {{ $p->stock }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <div x-show="currentStock !== null" class="mt-1 text-xs text-gray-500">
                        Current stock: <span class="font-semibold text-gray-700" x-text="currentStock"></span> units
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('quantity') border-red-400 @enderror">
                    @error('quantity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Link to order (for returns) --}}
                <div x-show="type === 'return_in'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Related Order (optional)</label>
                    <select name="order_id"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">None</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                {{ $order->order_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Doc No</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" placeholder="e.g. ORD-20240101-001"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                    <textarea name="reason" rows="3" placeholder="Explain why this adjustment is being made…"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('reason') border-red-400 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end space-x-3 pt-2">
                    <a href="{{ route('admin.stock-adjustments.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl text-sm font-medium text-white transition"
                        :class="isOut ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'">
                        Apply Adjustment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
