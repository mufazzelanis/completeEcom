@extends('admin.settings.layout')
@section('settings-title', 'Order Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'orders') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Order Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order Number Prefix</label>
            <input type="text" name="order_prefix" value="{{ setting('order_prefix', 'ORD-') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="ORD-">
            <p class="text-xs text-gray-400 mt-1">Applied to all new orders. Example: ORD-2026-001</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ setting('currency_symbol','৳') }}</span>
                <input type="number" name="min_order_amount" value="{{ setting('min_order_amount', '0') }}"
                       min="0" step="1"
                       class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="auto_confirm_orders" value="0">
            <input type="checkbox" name="auto_confirm_orders" value="1" class="rounded text-orange-600"
                   @checked(setting('auto_confirm_orders','0') == '1')>
            <span class="text-sm text-gray-700">Auto-Confirm Orders</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="auto_invoice" value="0">
            <input type="checkbox" name="auto_invoice" value="1" class="rounded text-orange-600"
                   @checked(setting('auto_invoice','0') == '1')>
            <span class="text-sm text-gray-700">Auto-Generate Invoice</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="guest_checkout" value="0">
            <input type="checkbox" name="guest_checkout" value="1" class="rounded text-orange-600"
                   @checked(setting('guest_checkout','0') == '1')>
            <span class="text-sm text-gray-700">Allow Guest Checkout</span>
        </label>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Checkout Form Fields</h2>
    <p class="text-xs text-gray-500 -mt-2">Customize each field's label and placeholder text (e.g. translate to Bangla), and control whether it's required, optional, or hidden on the customer checkout form.</p>

    @php
    // key => [default label, default placeholder, mode options ('' key = no dropdown at all), default mode]
    $checkoutFieldRows = [
        'name'    => ['Full Name', '', null, 'required'],
        'phone'   => ['Phone', '01XXXXXXXXX', ['required' => 'Required', 'optional' => 'Optional'], 'required'],
        'address' => ['Address', 'Street address, house number, area...', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'required'],
        'city'    => ['City', 'Dhaka', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'required'],
        'state'   => ['District', 'Dhaka', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'optional'],
        'zip'     => ['ZIP Code', '1207', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'optional'],
        'country' => ['Country', '', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'optional'],
        'email'   => ['Email', '', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'optional'],
        'notes'   => ['Order Notes', 'Any special instructions...', ['required' => 'Required', 'optional' => 'Optional', 'hidden' => 'Hidden'], 'optional'],
    ];
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                <th class="px-3 py-2 text-left">Field</th>
                <th class="px-3 py-2 text-left">Custom Label</th>
                <th class="px-3 py-2 text-left">Placeholder Text</th>
                <th class="px-3 py-2 text-left w-40">Show As</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($checkoutFieldRows as $key => [$defaultLabel, $defaultPlaceholder, $modeOptions, $defaultMode])
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-600 whitespace-nowrap">{{ $defaultLabel }}</td>
                    <td class="px-3 py-2">
                        <input type="text" name="checkout_label_{{ $key }}" value="{{ setting('checkout_label_'.$key, $defaultLabel) }}"
                               placeholder="{{ $defaultLabel }}"
                               class="w-full border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-orange-500">
                    </td>
                    <td class="px-3 py-2">
                        @if($key === 'country')
                            <span class="text-xs text-gray-300">— not editable (auto-filled)</span>
                        @else
                            <input type="text" name="checkout_placeholder_{{ $key }}" value="{{ setting('checkout_placeholder_'.$key, $defaultPlaceholder) }}"
                                   placeholder="{{ $defaultPlaceholder ?: 'e.g. bilingual example text' }}"
                                   class="w-full border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-orange-500">
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($modeOptions === null)
                            <span class="text-xs text-gray-400">Always required</span>
                        @else
                            <select name="checkout_field_{{ $key }}" class="w-full border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-orange-500">
                                @foreach($modeOptions as $val => $optLabel)
                                <option value="{{ $val }}" @selected(setting('checkout_field_'.$key, $defaultMode) === $val)>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400">Phone can't be hidden — guest checkout identifies/creates the customer's account by phone number, so "Show As" for Phone only applies once a customer is already logged in.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Policies</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Return Policy</label>
        <textarea name="return_policy" rows="5"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Describe your return and refund policy...">{{ setting('return_policy', '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cancellation Policy</label>
        <textarea name="cancellation_policy" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                  placeholder="Describe your order cancellation policy...">{{ setting('cancellation_policy', '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Privacy Policy (URL)</label>
        <input type="url" name="privacy_policy_url" value="{{ setting('privacy_policy_url', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://yoursite.com/privacy">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions (URL)</label>
        <input type="url" name="terms_url" value="{{ setting('terms_url', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://yoursite.com/terms">
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Order Settings</button>
</div>
</form>
@endsection
