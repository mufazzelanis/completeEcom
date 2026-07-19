@extends('admin.settings.layout')
@section('settings-title', 'Tax Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'tax') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">VAT / Tax Configuration</h2>

    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="tax_enabled" value="0">
        <input type="checkbox" name="tax_enabled" id="tax_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('tax_enabled','0') == '1')>
        <span class="text-sm font-medium text-gray-700">Enable Tax / VAT</span>
    </label>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Name</label>
            <input type="text" name="tax_name" value="{{ setting('tax_name', 'VAT') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="VAT">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
            <input type="number" name="tax_rate" value="{{ setting('tax_rate', '0') }}"
                   min="0" max="100" step="0.01"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="15">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Type</label>
            <select name="tax_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="percentage" @selected(setting('tax_type','percentage')==='percentage')>Percentage (%)</option>
                <option value="fixed" @selected(setting('tax_type','percentage')==='fixed')>Fixed Amount</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price Display</label>
            <select name="tax_included" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="excluded" @selected(setting('tax_included','excluded')==='excluded')>Tax Excluded (add at checkout)</option>
                <option value="included" @selected(setting('tax_included','excluded')==='included')>Tax Included in Price</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Registration Number</label>
        <input type="text" name="tax_number" value="{{ setting('tax_number', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="VAT/BIN number (shown on invoices)">
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Tax Settings</button>
</div>
</form>
@endsection
