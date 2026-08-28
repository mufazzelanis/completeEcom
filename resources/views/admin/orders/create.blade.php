@extends('layouts.admin')
@section('title', 'Create Order')

@section('content')
@php
    // Client-side product picker needs id/name/sku/effective price/available stock as plain
    // JSON — same "load the small catalog once, filter in the browser" approach the landing
    // page builder's product search already uses (no new AJAX endpoint needed).
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'sku' => $p->sku,
        'price' => (float) ($p->sale_price ?: $p->price),
        'stock' => (int) $p->stock,
    ])->values();
@endphp

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.orders.index') }}" class="w-9 h-9 bg-white rounded-xl shadow-sm flex items-center justify-center text-gray-500 hover:text-orange-600 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-800">Create Order</h1>
        <p class="text-sm text-gray-500">For a customer who ordered over the phone, WhatsApp, or in person.</p>
    </div>
</div>

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-4">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.orders.store') }}" method="POST"
    x-data="{
        items: [{ key: 0, product_id: null, name: '', sku: '', price: 0, stock: 0, quantity: 1, query: '', open: false }],
        nextKey: 1,
        products: {{ Js::from($productsJson) }},
        shippingCharge: 0,
        discount: 0,
        get subtotal() { return this.items.reduce((s, i) => s + (i.price * i.quantity), 0); },
        get total() { return Math.max(0, this.subtotal + Number(this.shippingCharge || 0) - Number(this.discount || 0)); },
        addItem() { this.items.push({ key: this.nextKey++, product_id: null, name: '', sku: '', price: 0, stock: 0, quantity: 1, query: '', open: false }); },
        removeItem(i) { this.items.splice(i, 1); },
        pick(item, p) {
            item.product_id = p.id; item.name = p.name; item.sku = p.sku;
            item.price = p.price; item.stock = p.stock; item.query = p.name; item.open = false;
        },
        filtered(item) {
            const q = item.query.trim().toLowerCase();
            if (!q) return this.products;
            return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
        },
    }" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    @csrf

    <div class="lg:col-span-2 space-y-5">
        {{-- Customer --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">Customer</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Existing Customer <span class="text-gray-400 font-normal">(optional — search by name or phone)</span></label>
                <div class="relative"
                     x-data="{
                        query: '', open: false, results: [], loading: false,
                        async search() {
                            if (this.query.trim().length < 2) { this.results = []; this.open = false; return; }
                            this.loading = true;
                            try {
                                const res = await fetch('{{ route('admin.search.suggest') }}?q=' + encodeURIComponent(this.query));
                                const data = res.ok ? await res.json() : { customers: [] };
                                this.results = data.customers || [];
                            } catch (e) { this.results = []; }
                            this.loading = false;
                            this.open = true;
                        },
                        pick(c) {
                            document.querySelector('input[name=customer_id]').value = c.id;
                            document.querySelector('input[name=shipping_name]').value = c.name;
                            document.querySelector('input[name=shipping_phone]').value = c.phone || '';
                            this.query = c.name + (c.phone ? ' (' + c.phone + ')' : '');
                            this.open = false;
                        },
                     }" @click.outside="open = false">
                    <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="if(results.length) open = true"
                        placeholder="Type a name or phone number…" autocomplete="off"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="hidden" name="customer_id">
                    <div x-show="open" x-cloak x-transition class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg py-1">
                        <template x-for="c in results" :key="c.id">
                            <button type="button" @click="pick(c)" class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 text-gray-700">
                                <span x-text="c.name"></span>
                                <span class="text-gray-400 text-xs" x-text="c.phone ? ' — ' + c.phone : ''"></span>
                            </button>
                        </template>
                        <p x-show="!loading && results.length === 0" class="px-4 py-2 text-sm text-gray-400">No matching customer.</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Picking one fills in the fields below (still editable) and links the order to their account. Leave blank for a guest order.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="shipping_name" value="{{ old('shipping_name') }}" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="shipping_phone" value="{{ old('shipping_phone') }}" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="shipping_address" rows="2"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('shipping_address') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input type="text" name="shipping_city" value="{{ old('shipping_city') }}"
                    class="w-full sm:w-1/2 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Products --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">Products</h3>
            <div class="space-y-3">
                <template x-for="(item, i) in items" :key="item.key">
                    <div class="border border-gray-100 rounded-xl p-3 space-y-2">
                        <div class="flex items-start gap-2">
                            <div class="relative flex-1" @click.outside="item.open = false">
                                <input type="text" x-model="item.query" @focus="item.open = true" @input="item.open = true; if (!item.query) item.product_id = null"
                                    placeholder="Search product by name or SKU…" autocomplete="off"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <input type="hidden" :name="'product_id[' + i + ']'" :value="item.product_id">
                                <div x-show="item.open" x-cloak x-transition class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg py-1">
                                    <template x-for="p in filtered(item)" :key="p.id">
                                        <button type="button" @click="pick(item, p)" class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 flex items-center justify-between gap-2"
                                            :class="p.stock <= 0 ? 'opacity-40 cursor-not-allowed' : ''" :disabled="p.stock <= 0">
                                            <span class="text-gray-700" x-text="p.name"></span>
                                            <span class="text-xs text-gray-400 shrink-0" x-text="'Stock: ' + p.stock"></span>
                                        </button>
                                    </template>
                                    <p x-show="filtered(item).length === 0" class="px-4 py-2 text-sm text-gray-400">No products match.</p>
                                </div>
                            </div>
                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600 p-2 shrink-0" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" x-show="item.product_id">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                                <input type="number" min="1" :max="item.stock" x-model.number="item.quantity" :name="'quantity[' + i + ']'"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Unit Price <span class="text-gray-400">(editable)</span></label>
                                <input type="number" step="0.01" min="0" x-model.number="item.price" :name="'price[' + i + ']'"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <p class="text-xs" :class="item.stock > 0 ? 'text-gray-400' : 'text-red-500'" x-show="item.product_id" x-text="item.stock + ' in stock'"></p>
                    </div>
                </template>
            </div>
            <button type="button" @click="addItem()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Product
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">Notes</h3>
            <textarea name="notes" rows="3" placeholder="e.g. Customer called on 27 Aug, wants delivery this weekend…"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 sticky top-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Charge</label>
                <input type="number" step="0.01" min="0" name="shipping_charge" x-model.number="shippingCharge"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discount</label>
                <input type="number" step="0.01" min="0" name="discount" x-model.number="discount"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="cod">Cash on Delivery</option>
                    @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->slug }}">{{ $pm->name }}</option>
                    @endforeach
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $val => $label)
                        <option value="{{ $val }}" {{ $val === 'processing' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 space-y-1.5 text-sm border-t border-gray-100 pt-4">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span x-text="'৳' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Shipping</span>
                    <span x-text="'৳' + Number(shippingCharge || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Discount</span>
                    <span x-text="'−৳' + Number(discount || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 pt-1.5 border-t border-dashed border-gray-200">
                    <span>Total</span>
                    <span x-text="'৳' + total.toFixed(2)"></span>
                </div>
            </div>

            <button type="submit" class="w-full bg-orange-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-orange-700 transition">
                Create Order
            </button>
        </div>
    </div>
</form>
@endsection
