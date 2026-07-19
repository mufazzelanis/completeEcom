@extends('admin.settings.layout')
@section('settings-title', 'Shipping Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'shipping') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Free Shipping</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="free_shipping_enabled" value="0">
        <input type="checkbox" name="free_shipping_enabled" id="free_ship" value="1" class="rounded text-orange-600"
               @checked(setting('free_shipping_enabled','0') == '1')>
        <span class="text-sm font-medium text-gray-700">Enable Free Shipping</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount for Free Shipping</label>
        <div class="relative">
            <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ setting('currency_symbol','৳') }}</span>
            <input type="number" name="free_shipping_min" value="{{ setting('free_shipping_min', '999') }}"
                   min="0" step="1"
                   class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Flat Rate Shipping</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="flat_rate_enabled" value="0">
        <input type="checkbox" name="flat_rate_enabled" id="flat_ship" value="1" class="rounded text-orange-600"
               @checked(setting('flat_rate_enabled','1') == '1')>
        <span class="text-sm font-medium text-gray-700">Enable Flat Rate Shipping</span>
    </label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Flat Rate Amount</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ setting('currency_symbol','৳') }}</span>
                <input type="number" name="flat_rate_amount" value="{{ setting('flat_rate_amount', '60') }}"
                       min="0" step="1"
                       class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Flat Rate Label</label>
            <input type="text" name="flat_rate_label" value="{{ setting('flat_rate_label', 'Standard Delivery') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Delivery Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Delivery Days</label>
            <input type="text" name="delivery_days" value="{{ setting('delivery_days', '3-7') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="3-7">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Zones / Areas</label>
            <input type="text" name="shipping_zones" value="{{ setting('shipping_zones', 'Dhaka, Chittagong, Sylhet') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="Comma-separated zones">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Charge Rules (visible to customers)</label>
            <textarea name="delivery_charge_rules" rows="3"
                      class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                      placeholder="e.g. Free delivery on orders above ৳999. ৳60 for orders below ৳999.">{{ setting('delivery_charge_rules', '') }}</textarea>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Shipping</button>
</div>
</form>
@endsection
