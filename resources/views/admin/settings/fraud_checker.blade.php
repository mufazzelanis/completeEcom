@extends('admin.settings.layout')
@section('settings-title', 'Fraud Checker')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'fraud_checker') }}">
@csrf @method('PATCH')

<div class="bg-blue-50 border border-blue-100 text-blue-800 rounded-xl px-4 py-3 text-xs mb-6">
    Enable any combination below — your own courier accounts, the bdcourier.com aggregator, or
    both. A courier configured directly here always takes priority over the aggregator's number
    for that same courier, so enabling both never double-counts.
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mb-4">
    <div class="flex items-center justify-between pb-2 border-b">
        <h2 class="text-base font-semibold text-gray-900">Steadfast Courier</h2>
        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Ready</span>
    </div>
    <p class="text-xs text-gray-400">
        Get an Api Key + Secret Key from Steadfast's merchant panel (Settings → API Support)
        once your merchant account is approved.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Api Key</label>
            <input type="password" name="courier_steadfast_api_key" value="{{ setting('courier_steadfast_api_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
            <input type="password" name="courier_steadfast_secret_key" value="{{ setting('courier_steadfast_secret_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
    </div>
</div>

@foreach([
    ['key' => 'pathao', 'label' => 'Pathao Courier'],
    ['key' => 'redx', 'label' => 'RedX'],
    ['key' => 'paperfly', 'label' => 'Paperfly'],
    ['key' => 'ecourier', 'label' => 'eCourier'],
] as $courier)
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-3 mb-4">
    <div class="flex items-center justify-between pb-2 border-b">
        <h2 class="text-base font-semibold text-gray-900">{{ $courier['label'] }}</h2>
        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">Coming Soon</span>
    </div>
    <p class="text-xs text-gray-400">
        Credentials are saved and ready, but the actual delivery-history check isn't wired up
        yet — {{ $courier['label'] }}'s official API documentation (or a working example
        request/response from your merchant account) is needed first so this returns real data
        instead of a guess. Send it over and this gets finished properly.
    </p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
        <input type="password" name="courier_{{ $courier['key'] }}_api_key" value="{{ setting('courier_'.$courier['key'].'_api_key', '') }}"
               class="w-full md:w-1/2 border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="••••••••" autocomplete="new-password">
    </div>
</div>
@endforeach

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mb-4">
    <div class="flex items-center justify-between pb-2 border-b">
        <h2 class="text-base font-semibold text-gray-900">bdcourier.com (Aggregator)</h2>
        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Optional</span>
    </div>
    <p class="text-xs text-gray-400">
        Optional alternative that checks several couriers in one request instead of a separate
        account per courier — useful as a quick stopgap, or to fill in any courier above you
        haven't configured directly yet. Get a key at
        <a href="https://bdcourier.com" target="_blank" rel="noopener" class="text-orange-600 hover:underline">bdcourier.com</a>.
    </p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
        <input type="password" name="courier_bdcourier_api_key" value="{{ setting('courier_bdcourier_api_key', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="••••••••" autocomplete="new-password">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API URL</label>
        <input type="text" name="courier_bdcourier_api_url" value="{{ setting('courier_bdcourier_api_url', 'https://bdcourier.com/api/courier-check') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Risk Scoring</h2>
    <p class="text-xs text-gray-400">
        A phone's "success rate" is delivered parcels ÷ (delivered + cancelled), combined
        across every courier configured above that has a record for it.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Orders to Judge</label>
            <input type="number" name="courier_fraud_min_orders" value="{{ setting('courier_fraud_min_orders', '2') }}"
                   min="1" max="20"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Below this many total parcels, shown as "Not Enough Data" instead of a risk label.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">High Risk Below (%)</label>
            <input type="number" name="courier_fraud_high_risk_below" value="{{ setting('courier_fraud_high_risk_below', '50') }}"
                   min="1" max="100"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Success rate below this = High Risk (red).</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Risky Below (%)</label>
            <input type="number" name="courier_fraud_medium_risk_below" value="{{ setting('courier_fraud_medium_risk_below', '75') }}"
                   min="1" max="100"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <p class="text-xs text-gray-400 mt-1">Success rate below this (but above High Risk) = Risky (yellow). Above it = Safe (green).</p>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cache Duration (hours)</label>
        <input type="number" name="courier_fraud_cache_hours" value="{{ setting('courier_fraud_cache_hours', '24') }}"
               min="1" max="720"
               class="w-full md:w-1/3 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">
            Re-opening an order or re-checking the same number within this window reuses the
            last result instead of calling every provider again. "Re-check" always forces a
            fresh call regardless of this.
        </p>
    </div>
</div>

<div class="flex justify-end mt-4">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Fraud Checker Settings</button>
</div>
</form>
@endsection
