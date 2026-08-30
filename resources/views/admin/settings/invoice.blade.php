@extends('admin.settings.layout')
@section('settings-title', 'Invoice Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'invoice') }}" enctype="multipart/form-data"
      x-data="{
          accent: '{{ setting('invoice_accent_color', '#6366f1') }}',
          dark: '{{ setting('invoice_dark_color', '#111827') }}',
      }">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Invoice Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Prefix</label>
            <input type="text" name="invoice_prefix" value="{{ setting('invoice_prefix', 'INV-') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="INV-">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Starting Number</label>
            <input type="number" name="invoice_start_number" value="{{ setting('invoice_start_number', '1000') }}"
                   min="1"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
    @php
        $previewPrefix = setting('invoice_prefix', 'INV-');
        $previewStart = (int) setting('invoice_start_number', 1000);
    @endphp
    <p class="text-xs text-gray-400">Invoice numbers are generated sequentially from the order id — e.g. your next few invoices will read <span class="font-mono text-gray-600">{{ $previewPrefix }}{{ str_pad($previewStart, 6, '0', STR_PAD_LEFT) }}</span>, <span class="font-mono text-gray-600">{{ $previewPrefix }}{{ str_pad($previewStart + 1, 6, '0', STR_PAD_LEFT) }}</span>, <span class="font-mono text-gray-600">{{ $previewPrefix }}{{ str_pad($previewStart + 2, 6, '0', STR_PAD_LEFT) }}</span>…</p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Logo</label>
        @php $logoUrl = setting_file_url('invoice_logo'); @endphp
        @if($logoUrl)
        <div class="flex items-center gap-3 mb-2">
            <img src="{{ $logoUrl }}" alt="Invoice Logo" class="h-10 max-w-[120px] object-contain rounded border p-1 bg-gray-50">
            <span class="text-xs text-green-600">Uploaded</span>
        </div>
        @endif
        <input type="file" name="invoice_logo" accept="image/*"
               class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
        <p class="text-xs text-gray-400 mt-1">Recommended: 200×60px.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Invoice Content</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Footer Text</label>
        <textarea name="invoice_footer_text" rows="3"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Thank you for your purchase!">{{ setting('invoice_footer_text', 'Thank you for your business!') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions (on invoice)</label>
        <textarea name="invoice_terms" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Return policy, warranty, etc.">{{ setting('invoice_terms', '') }}</textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Due (days)</label>
            <input type="number" name="invoice_due_days" value="{{ setting('invoice_due_days', '0') }}"
                   min="0"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">0 = due immediately</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Invoice Colors</h2>
    <p class="text-xs text-gray-400 -mt-2">Re-theme the downloaded/printed PDF invoice to match your brand. Accent colors the invoice number, section labels, note borders and footer highlight. Header/Total Bar colors the items-table header and the total row.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="invoice_accent_color" x-model="accent"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" x-model="accent"
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Header / Total Bar Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="invoice_dark_color" x-model="dark"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" x-model="dark"
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
            </div>
        </div>
    </div>

    {{-- Live Preview --}}
    <div class="border-t pt-4 mt-2">
        <p class="text-xs font-medium text-gray-500 mb-2">Preview:</p>
        <div class="rounded-xl border p-4 bg-gray-50 space-y-3">
            <div class="h-1.5 rounded" :style="`background-color: ${accent}`"></div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold" :style="`color: ${accent}`">{{ setting('company_name') ?: setting('site_name', 'ShopVista') }}</div>
                    <div class="text-[10px] text-gray-400">123 Example Road, Dhaka, Bangladesh</div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-extrabold tracking-widest text-gray-800">INVOICE</div>
                    <div class="text-[10px] font-bold" :style="`color: ${accent}`">INV-001000</div>
                </div>
            </div>
            <div class="rounded overflow-hidden text-[10px]">
                <div class="flex px-3 py-1.5 text-white font-semibold" :style="`background-color: ${dark}`">
                    <span class="flex-1">Product</span><span>Subtotal</span>
                </div>
                <div class="flex px-3 py-1.5 bg-white border-x border-b">
                    <span class="flex-1 text-gray-600">বাংলা প্রোডাক্ট (Sample Bangla Text)</span><span class="text-gray-800 font-semibold">৳ 1,250.00</span>
                </div>
                <div class="flex px-3 py-1.5 text-white font-bold" :style="`background-color: ${dark}`">
                    <span class="flex-1">Total</span><span>৳ 1,250.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Invoice Settings</button>
</div>
</form>
@endsection
