@extends('admin.settings.layout')
@section('settings-title', 'Shipping Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'shipping') }}"
      x-data="{ method: '{{ setting('shipping_method', 'zone') }}' }">
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
        <p class="text-xs text-gray-400 mt-1">Applies on top of whichever delivery charge method is selected below — orders at or above this amount always ship free.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Delivery Charge Method</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <label class="flex items-start gap-3 border rounded-xl p-4 cursor-pointer transition"
               :class="method === 'zone' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'">
            <input type="radio" name="shipping_method" value="zone" x-model="method" class="mt-1">
            <span>
                <span class="block text-sm font-semibold text-gray-800">Dhaka / Outside Dhaka (recommended)</span>
                <span class="block text-xs text-gray-500 mt-0.5">Charge one rate inside Dhaka and a different rate for the rest of the country. The customer picks their delivery area at checkout.</span>
            </span>
        </label>
        <label class="flex items-start gap-3 border rounded-xl p-4 cursor-pointer transition"
               :class="method === 'flat' ? 'border-orange-500 bg-orange-50' : 'border-gray-200'">
            <input type="radio" name="shipping_method" value="flat" x-model="method" class="mt-1">
            <span>
                <span class="block text-sm font-semibold text-gray-800">Flat Rate</span>
                <span class="block text-xs text-gray-500 mt-0.5">Charge the same delivery fee for every order, regardless of location.</span>
            </span>
        </label>
    </div>

    <div x-show="method === 'zone'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Inside Dhaka Charge</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ setting('currency_symbol','৳') }}</span>
                <input type="number" name="dhaka_charge" value="{{ setting('dhaka_charge', '60') }}"
                       min="0" step="1"
                       class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Outside Dhaka Charge</label>
            <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500 text-sm">{{ setting('currency_symbol','৳') }}</span>
                <input type="number" name="outside_dhaka_charge" value="{{ setting('outside_dhaka_charge', '120') }}"
                       min="0" step="1"
                       class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
    </div>

    {{-- Which area an order starts on: checkout, cart estimate, pre-order and landing pages all
         read it (App\Services\ShippingCalculator::defaultZone). --}}
    @php $defZone = setting('shipping_default_zone', 'outside_dhaka'); $defZone = in_array($defZone, ['dhaka', 'outside_dhaka', 'ask'], true) ? $defZone : 'outside_dhaka'; @endphp
    <div x-show="method === 'zone'" x-cloak class="pt-4 border-t border-gray-100" x-data="{ def: @js($defZone) }">
        <label class="block text-sm font-semibold text-gray-800">Default Delivery Area</label>
        <p class="text-xs text-gray-500 mt-0.5 mb-3">Already selected when a customer opens checkout, a landing page order form or a pre-order — they can still switch. Also used for the delivery estimate in the cart.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach([
                'dhaka' => ['Inside Dhaka', 'Most of your customers are in Dhaka.', 'dhaka_charge', '60'],
                'outside_dhaka' => ['Outside Dhaka', 'Most orders ship outside Dhaka. The higher rate shows first, so a total never goes up later.', 'outside_dhaka_charge', '120'],
                'ask' => ['Customer chooses', 'Nothing preselected — every customer picks their area before ordering, so no one pays a rate they didn\'t choose.', null, null],
            ] as $value => [$title, $hint, $chargeKey, $chargeDefault])
            <label class="relative flex flex-col gap-1 border-2 rounded-xl p-4 cursor-pointer transition"
                   :class="def === '{{ $value }}' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-gray-300'">
                <input type="radio" name="shipping_default_zone" value="{{ $value }}" x-model="def" class="absolute top-4 right-4">
                <span class="text-sm font-semibold text-gray-800 pr-6">{{ $title }}</span>
                @if($chargeKey)
                    <span class="text-lg font-extrabold text-gray-900">{{ setting('currency_symbol', '৳') }}{{ number_format((float) setting($chargeKey, $chargeDefault)) }}</span>
                @else
                    <span class="text-lg font-extrabold text-gray-900">—</span>
                @endif
                <span class="text-xs text-gray-500 leading-relaxed">{{ $hint }}</span>
            </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-2">A returning customer's own last choice is remembered on their device and wins over this default.</p>
    </div>

    <div x-show="method === 'flat'" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
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
    <div class="grid grid-cols-1 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Delivery Days</label>
            <input type="text" name="delivery_days" value="{{ setting('delivery_days', '3-7') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="3-7">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Note (shown to customers at checkout)</label>
            <textarea name="delivery_charge_rules" rows="3"
                      class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                      placeholder="e.g. Cash on delivery available nationwide. Delivery within 3-7 business days.">{{ setting('delivery_charge_rules', '') }}</textarea>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Shipping</button>
</div>
</form>
@endsection
