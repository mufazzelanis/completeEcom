@extends('layouts.admin')
@section('title', 'Barcode Management')

@section('content')
<style>[x-cloak]{display:none!important}</style>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Barcode Management</h1>
        <p class="text-sm text-gray-500 mt-1">View, edit and print product barcodes</p>
    </div>
    <button onclick="window.print()" class="border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2 print:hidden">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print Labels
    </button>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm print:hidden">{{ session('success') }}</div>@endif

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6 print:hidden">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, SKU or barcode…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="has_barcode" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            <option value="yes" {{ request('has_barcode')==='yes' ? 'selected' : '' }}>Has Barcode</option>
            <option value="no" {{ request('has_barcode')==='no' ? 'selected' : '' }}>No Barcode</option>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        @if(request()->hasAny(['search','category','has_barcode']))<a href="{{ route('admin.barcodes.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
    </form>
</div>

{{-- Bulk Edit Form --}}
<form action="{{ route('admin.barcodes.bulk-update') }}" method="POST">
@csrf

<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between print:hidden">
        <p class="text-sm text-gray-500">{{ $products->total() }} products</p>
        <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Save All Barcodes</button>
    </div>

    {{-- Print Grid --}}
    <div class="p-6 grid grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($products as $product)
        <div class="border border-gray-200 rounded-2xl p-4 text-center" x-data="{ bc: '{{ $product->barcode }}' }">
            {{-- Barcode image via JsBarcode --}}
            <div class="mb-3 flex justify-center">
                @if($product->barcode)
                <svg class="barcode" :id="`bc-{{ $product->id }}`"
                    jsbarcode-value="{{ $product->barcode }}"
                    jsbarcode-width="1.5"
                    jsbarcode-height="40"
                    jsbarcode-fontsize="10"
                    jsbarcode-margin="4">
                </svg>
                @else
                <div class="w-full h-14 bg-gray-100 rounded-xl flex items-center justify-center">
                    <p class="text-xs text-gray-400">No barcode</p>
                </div>
                @endif
            </div>

            <p class="text-sm font-semibold text-gray-800 truncate mb-1">{{ $product->name }}</p>
            <p class="text-xs text-gray-400 font-mono mb-3">{{ $product->sku ?? 'No SKU' }}</p>

            <div class="print:hidden">
                <input type="text" name="barcodes[{{ $product->id }}]" value="{{ $product->barcode }}"
                    placeholder="Enter barcode…"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-center font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="hidden print:block text-xs font-mono text-gray-600 mt-1">{{ $product->barcode }}</div>
        </div>
        @empty
        <div class="col-span-3 py-12 text-center text-gray-400">No products found.</div>
        @endforelse
    </div>
</div>

</form>

@if($products->hasPages())
<div class="print:hidden">{{ $products->links() }}</div>
@endif

<style>
@media print {
    .print\:hidden { display: none !important; }
    .print\:block { display: block !important; }
    header, nav, aside, footer { display: none !important; }
    .hidden.print\:block { display: block !important; }
    body { font-size: 10pt; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof JsBarcode !== 'undefined') {
        try { JsBarcode('.barcode').init(); } catch(e) {}
    }
});
</script>
@endsection
