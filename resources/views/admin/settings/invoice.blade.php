@extends('admin.settings.layout')
@section('settings-title', 'Invoice Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'invoice') }}" enctype="multipart/form-data">
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

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Invoice Settings</button>
</div>
</form>
@endsection
