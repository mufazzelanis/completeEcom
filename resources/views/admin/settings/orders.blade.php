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

{{-- Pre-orders: out-of-stock products get a "Pre-order" button — the shopper pays only the
     delivery charge by bKash / Nagad (Payment Methods → their numbers) and the rest on delivery --}}
@php $poMethods = \App\Models\PaymentMethod::where('is_active', true)->where('type', 'mobile_banking')->get(['name', 'account_number']); @endphp
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">📦 Pre-orders</h3>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="preorder_enabled" value="0">
            <input type="checkbox" name="preorder_enabled" value="1" class="rounded text-orange-600" @checked(setting('preorder_enabled', '1') == '1')>
            <span class="text-sm text-gray-700">Allow pre-orders on out-of-stock products</span>
        </label>
    </div>
    <p class="text-sm text-gray-500">The customer pays only the delivery charge now by bKash / Nagad (Send Money + Transaction ID), the rest on delivery. Works for products without size/colour options. Pre-orders don't change stock.</p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery time note (shown to customers)</label>
        <input type="text" name="preorder_note" maxlength="200" value="{{ setting('preorder_note', 'We ship as soon as the new stock arrives — usually within 7–15 days. We\'ll call you to confirm.') }}"
               class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
    </div>
    <div class="text-sm rounded-lg px-4 py-3 {{ $poMethods->isEmpty() ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-600' }}">
        @if($poMethods->isEmpty())
            ⚠️ No bKash / Nagad payment method is active, so the Pre-order button stays hidden. Turn one on in <a href="{{ route('admin.payment-methods.index') }}" class="underline font-semibold">Payment Methods</a>.
        @else
            Advance is paid to:
            @foreach($poMethods as $m)
                <strong>{{ $m->name }}</strong> {{ $m->account_number ?: '— ⚠️ number not set' }}@if(!$loop->last) · @endif
            @endforeach
            — <a href="{{ route('admin.payment-methods.index') }}" class="underline">edit numbers</a>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Phone Number Check (fake-order protection)</h2>
    @php
        $phoneCheck = \App\Support\PhoneValidator::mode();
        $gatewayOk = \App\Http\Controllers\CheckoutPhoneController::gatewayConfigured();
    @endphp
    <div class="space-y-2">
        @foreach([
            'off' => ['Off', 'Accept any phone number.'],
            'format' => ['Valid Bangladeshi mobile number (recommended)', 'Must be a real-looking BD mobile (013–019, 11 digits). Blocks 01711111111, 01712345678 and similar keyboard-mash numbers. Applies to checkout and landing pages.'],
            'otp' => ['Format check + SMS code (proves the number is active)', 'The customer gets a 6-digit SMS code and must enter it before the order is placed (main checkout). Needs an SMS gateway; landing pages get the format check only.'],
        ] as $val => [$title, $hint])
            <label class="flex items-start gap-2 cursor-pointer">
                <input type="radio" name="phone_check" value="{{ $val }}" class="mt-1 text-orange-600" @checked($phoneCheck === $val)>
                <span><span class="text-sm font-medium text-gray-800">{{ $title }}</span><span class="block text-xs text-gray-500">{{ $hint }}</span></span>
            </label>
        @endforeach
    </div>
    <div class="text-xs rounded-lg p-3 {{ $gatewayOk ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
        @if($gatewayOk)
            SMS gateway is configured ({{ config('notifications.sms.driver') }}). SMS codes will be sent.
        @else
            No SMS gateway is configured yet, so the SMS-code option has no effect on the live site (orders are never blocked because of it). Set <code>SMS_DRIVER=http</code> and <code>SMS_HTTP_URL</code> in the server's .env or the Twilio keys.
        @endif
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Return Window (days)</label>
        <input type="number" name="return_days" value="{{ setting('return_days', '7') }}"
               min="0" step="1"
               class="w-32 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">Shown as a "{{ setting('return_days', '7') }}-day return policy" badge on product pages. Set to 0 to hide the badge.</p>
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
