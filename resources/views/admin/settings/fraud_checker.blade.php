@extends('admin.settings.layout')
@section('settings-title', 'Fraud Checker')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'fraud_checker') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Courier Delivery History Check</h2>
    <p class="text-xs text-gray-400">
        Checks a customer's phone number against Bangladesh's major courier services (Pathao,
        Steadfast, RedX, Paperfly, eCourier, and others) to see their past delivery-success vs.
        cancelled/returned parcel history — the single most useful signal for deciding whether to
        confirm a Cash on Delivery order. Powered by
        <a href="https://bdcourier.com" target="_blank" rel="noopener" class="text-orange-600 hover:underline">bdcourier.com</a>'s
        aggregator API, which checks all of them in one request instead of needing a separate
        merchant account per courier. Get an API key by registering there.
    </p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
        <input type="password" name="courier_fraud_api_key" value="{{ setting('courier_fraud_api_key', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="••••••••" autocomplete="new-password">
        <p class="text-xs text-gray-400 mt-1">
            {{ setting('courier_fraud_api_key', '') !== '' ? 'A key is currently saved — leave as-is to keep it, or paste a new one to replace it.' : 'Not configured yet — the checker will show an error until a key is added here.' }}
        </p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API URL</label>
        <input type="text" name="courier_fraud_api_url" value="{{ setting('courier_fraud_api_url', 'https://bdcourier.com/api/courier-check') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500">
        <p class="text-xs text-gray-400 mt-1">
            Only change this if bdcourier.com's documented endpoint differs from this default, or
            you're switching to a different provider entirely.
        </p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Risk Scoring</h2>
    <p class="text-xs text-gray-400">
        A phone's "success rate" is delivered parcels ÷ (delivered + cancelled) across every
        courier that has a record for it. These thresholds decide which color/label that rate
        gets shown as.
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
            Re-opening an order or re-checking the same number within this window reuses the last
            result instead of calling the API again — keeps API usage down. The "Re-check" button
            always forces a fresh call regardless of this.
        </p>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Fraud Checker Settings</button>
</div>
</form>
@endsection
