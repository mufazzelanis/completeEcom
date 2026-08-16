@extends('admin.settings.layout')
@section('settings-title', 'Google Analytics & Ads')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'google_ads') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Google Analytics (GA4)</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Measurement ID</label>
            <input type="text" name="google_analytics_id" value="{{ setting('google_analytics_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="G-XXXXXXXXXX">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google Tag Manager ID</label>
            <input type="text" name="google_tag_manager_id" value="{{ setting('google_tag_manager_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="GTM-XXXXXXX">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Google Ads Conversion Tracking</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Conversion ID</label>
            <input type="text" name="google_ads_conversion_id" value="{{ setting('google_ads_conversion_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="AW-XXXXXXXXX">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Conversion Label</label>
            <input type="text" name="google_ads_purchase_label" value="{{ setting('google_ads_purchase_label', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="AbCdEfGhIj-kLmNoPqR">
        </div>
    </div>
    <p class="text-xs text-gray-400">
        Both from Google Ads → Tools &amp; Settings → Conversions → your Purchase action → "Use Google tag". Every
        completed order fires this as a conversion, valued at the order total.
    </p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Enhanced Conversions</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="google_enhanced_conversions_enabled" value="0">
        <input type="checkbox" name="google_enhanced_conversions_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('google_enhanced_conversions_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Send customer details to Google for Enhanced Conversions</span>
    </label>
    <p class="text-xs text-gray-400">
        When on, a logged-in customer's name/email/phone (and, at checkout, the order's shipping
        details) are handed to Google's tag as <code class="font-mono">user_data</code> — hashed in
        the browser before sending, same as Meta's Advanced Matching. This improves Google Ads
        conversion measurement accuracy, especially when cookies are blocked.
    </p>
</div>

<div class="flex justify-end mt-6">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Google Settings</button>
</div>
</form>
@endsection
