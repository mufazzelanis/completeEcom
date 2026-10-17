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
    </div>
    <p class="text-xs text-gray-400">Sends page views and all shop events (product views, cart, checkout, purchases) to GA4. Don't add GA4 again inside Tag Manager — every visit and sale would be counted twice.</p>
</div>

{{-- Google Tag Manager --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6" x-data="{ on: {{ setting('google_tag_manager_enabled', '1') === '1' ? 'true' : 'false' }} }">
    <div class="flex items-start justify-between gap-4 pb-2 border-b">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Google Tag Manager</h2>
            <p class="text-xs text-gray-500 mt-0.5">Add other tracking tools (TikTok Pixel, Google Ads remarketing, LinkedIn…) from tagmanager.google.com without changing the site.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
            <input type="hidden" name="google_tag_manager_enabled" value="0">
            <input type="checkbox" name="google_tag_manager_enabled" value="1" class="sr-only peer" x-model="on">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-green-600"></div>
            <span class="ml-2 text-sm font-medium" :class="on ? 'text-green-700' : 'text-gray-500'" x-text="on ? 'On' : 'Off'"></span>
        </label>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Container ID</label>
        <input type="text" name="google_tag_manager_id" value="{{ setting('google_tag_manager_id', '') }}"
               class="w-full md:w-1/2 border rounded-lg px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-orange-500"
               placeholder="GTM-XXXXXXX" pattern="[Gg][Tt][Mm]-[A-Za-z0-9]{4,12}" title="Like GTM-ABC1234">
    </div>
    <ul class="text-xs text-gray-500 space-y-1 list-disc pl-5">
        <li>Ignore the install code Tag Manager shows you — the site adds it itself.</li>
        <li>Shop events are already in the data layer for your triggers (Custom Event): <code class="font-mono">view_item</code>, <code class="font-mono">add_to_cart</code>, <code class="font-mono">begin_checkout</code>, <code class="font-mono">purchase</code>, <code class="font-mono">view_item_list</code>, <code class="font-mono">select_item</code>, <code class="font-mono">view_cart</code>, <code class="font-mono">remove_from_cart</code>, plus <code class="font-mono">virtualPageview</code> on every page change.</li>
        <li><strong>Don't add a GA4 or Facebook Pixel tag in Tag Manager</strong> — both already run on the site; they'd count everything twice.</li>
        <li>Loads a moment after the page (or on the first scroll/tap) so it doesn't slow the page down; no event is lost.</li>
    </ul>
</div>

{{-- Microsoft Clarity --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6" x-data="{ on: {{ setting('clarity_enabled', '0') === '1' ? 'true' : 'false' }} }">
    <div class="flex items-start justify-between gap-4 pb-2 border-b">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Microsoft Clarity <span class="text-xs font-normal text-gray-500">(free)</span></h2>
            <p class="text-xs text-gray-500 mt-0.5">Session recordings and heatmaps — watch how shoppers move through the site and where they give up.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
            <input type="hidden" name="clarity_enabled" value="0">
            <input type="checkbox" name="clarity_enabled" value="1" class="sr-only peer" x-model="on">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-green-600"></div>
            <span class="ml-2 text-sm font-medium" :class="on ? 'text-green-700' : 'text-gray-500'" x-text="on ? 'On' : 'Off'"></span>
        </label>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Project ID</label>
        <input type="text" name="clarity_project_id" value="{{ setting('clarity_project_id', '') }}"
               class="w-full md:w-1/2 border rounded-lg px-3 py-2 text-sm font-mono lowercase focus:ring-2 focus:ring-orange-500"
               placeholder="e.g. abc1de2fgh" pattern="[A-Za-z0-9]{6,16}" title="The 10-character ID from Clarity → Settings → Overview">
    </div>
    <ul class="text-xs text-gray-500 space-y-1 list-disc pl-5">
        <li>clarity.microsoft.com → New project → name Mitavin, site https://mitavin.com → Settings → Overview → copy the <strong>Project ID</strong>.</li>
        <li>Clarity masks what customers type (names, phone numbers, addresses) by default.</li>
        <li>Optional: in Clarity → Settings → Setup, connect Google Analytics to open a recording straight from GA4.</li>
    </ul>
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
