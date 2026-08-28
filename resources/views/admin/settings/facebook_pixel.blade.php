@extends('admin.settings.layout')
@section('settings-title', 'Facebook Pixel')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'facebook_pixel') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Facebook / Meta Pixel</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pixel ID</label>
        <input type="text" name="facebook_pixel_id" value="{{ setting('facebook_pixel_id', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="123456789012345">
        <p class="text-xs text-gray-400 mt-1">Find this in Meta Events Manager → Data Sources → your pixel.</p>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="facebook_pixel_enabled" value="0">
        <input type="checkbox" name="facebook_pixel_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('facebook_pixel_enabled', setting('facebook_pixel_id', '') ? '1' : '0') == '1')>
        <span class="text-sm text-gray-700">Enable Pixel tracking</span>
    </label>
    <p class="text-xs text-gray-400">Turn off to pause tracking without losing the saved Pixel ID.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Advanced Matching</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="facebook_advanced_matching_enabled" value="0">
        <input type="checkbox" name="facebook_advanced_matching_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('facebook_advanced_matching_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Send customer details to Meta for Advanced Matching</span>
    </label>
    <p class="text-xs text-gray-400">
        When on, a logged-in customer's name/email/phone (and, at checkout, the order's shipping
        details) are handed to Meta's pixel — which hashes them in the browser before sending,
        so raw personal data never leaves the visitor's browser unhashed. This is what lets Meta
        match your site visitors and buyers to their ad accounts for better targeting and reporting,
        even when cookies are blocked.
    </p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <div class="flex items-center justify-between pb-2 border-b">
        <h2 class="text-base font-semibold text-gray-900">Conversions API (Server-Side Events)</h2>
        @if(\App\Services\Facebook\ConversionsApi::isEnabled())
            <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View Recent Events →</a>
        @endif
    </div>
    <p class="text-xs text-gray-500">
        The Pixel above only fires from the visitor's browser — ad blockers, iOS tracking
        protections, and blocked third-party scripts routinely stop it before it ever reaches
        Meta, silently under-reporting your real conversions and ad performance. The
        Conversions API sends the same events directly from this server instead, immune to all
        of that, and Meta automatically merges each pair (browser + server) into a single
        conversion rather than double-counting — <strong>Meta itself recommends running both
        together</strong>, not either alone.
    </p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Conversions API Access Token</label>
        <input type="password" name="facebook_capi_access_token" value="{{ setting('facebook_capi_access_token', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="EAAG..." autocomplete="new-password">
        <p class="text-xs text-gray-400 mt-1">
            Meta Events Manager → Data Sources → your pixel → Settings → Conversions API →
            "Generate access token". This token authenticates every server-side event and is
            tied to this Pixel ID above.
        </p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Test Event Code <span class="text-gray-400 font-normal">(optional)</span></label>
        <input type="text" name="facebook_capi_test_event_code" value="{{ setting('facebook_capi_test_event_code', '') }}"
               class="w-full sm:w-64 border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="TEST12345">
        <p class="text-xs text-gray-400 mt-1">
            Paste this from Events Manager → Test Events while verifying your setup, to see
            events land there in real time instead of counted as live traffic. Remove it again
            once confirmed — events sent with a test code are excluded from your real reporting.
        </p>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="facebook_capi_enabled" value="0">
        <input type="checkbox" name="facebook_capi_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('facebook_capi_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Enable Conversions API</span>
    </label>
    <p class="text-xs text-gray-400">
        Sends ViewContent, AddToCart, InitiateCheckout, and Purchase from the server for every
        page/order this app already tracks with the Pixel — no separate setup needed per event.
        Requires the Pixel ID above and an access token; turning this on without either does
        nothing (nothing will send with a missing token, and nothing is logged as an error).
    </p>
</div>

<div class="flex justify-end mt-6">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Facebook Pixel Settings</button>
</div>
</form>
@endsection
