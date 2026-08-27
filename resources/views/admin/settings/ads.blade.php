@extends('admin.settings.layout')
@section('settings-title', 'Advertisements')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'ads') }}">
@csrf @method('PATCH')

<div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
    Ads only ever show on the <strong>Blog</strong> — never on the shop, product pages, cart,
    checkout, or landing pages, where they'd distract customers away from actually buying.
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Google AdSense</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Publisher ID</label>
        <input type="text" name="adsense_publisher_id" value="{{ setting('adsense_publisher_id', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
               placeholder="ca-pub-1234567890123456">
        <p class="text-xs text-gray-400 mt-1">AdSense → Account → Settings → Account information. Required for any ad below to show.</p>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="ads_enabled" value="0">
        <input type="checkbox" name="ads_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('ads_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Enable ads on the Blog</span>
    </label>
    <p class="text-xs text-gray-400">Turn off to pull every ad without losing the saved Publisher ID / slot IDs below.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Ad Placements</h2>
    <p class="text-xs text-gray-400 -mt-2">
        Each placement is its own <a href="https://www.google.com/adsense" target="_blank" rel="noopener" class="underline">AdSense ad unit</a> —
        create one per slot below and paste in just its Ad Slot ID (the number after <code class="font-mono">data-ad-slot=</code> in the code
        AdSense gives you). Leave any of them blank to simply skip that one spot.
    </p>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">In-Feed Slot <span class="text-gray-400 font-normal">(between post cards on the Blog listing)</span></label>
        <input type="text" name="adsense_slot_infeed" value="{{ setting('adsense_slot_infeed', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500" placeholder="1234567890">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">In-Article Slot <span class="text-gray-400 font-normal">(inside a post, after the intro)</span></label>
        <input type="text" name="adsense_slot_article" value="{{ setting('adsense_slot_article', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500" placeholder="1234567890">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sidebar Slot <span class="text-gray-400 font-normal">(post page sidebar)</span></label>
        <input type="text" name="adsense_slot_sidebar" value="{{ setting('adsense_slot_sidebar', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500" placeholder="1234567890">
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Adsterra</h2>
    <p class="text-xs text-gray-400 -mt-2">
        Adsterra ships several ad formats (Banner, Native, Social Bar…), each with its own
        snippet shape — rather than one fixed field, paste the <strong>entire code block</strong>
        Adsterra gives you for each placement below (the <code class="font-mono">&lt;script&gt;</code> tags and all).
        Leave any of them blank to skip that spot. Stick to Banner/Native formats here — a
        popunder or direct-link snippet pasted into a placement will fire that popup/redirect
        the moment the page loads, on a page still carrying your store's branding.
    </p>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="adsterra_enabled" value="0">
        <input type="checkbox" name="adsterra_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('adsterra_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Enable Adsterra on the Blog</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">In-Feed Code <span class="text-gray-400 font-normal">(further down the Blog listing, separate from the AdSense in-feed slot above)</span></label>
        <textarea name="adsterra_code_infeed" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="<script>...</script>">{{ setting('adsterra_code_infeed', '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">In-Article Code <span class="text-gray-400 font-normal">(end of a post, after the content)</span></label>
        <textarea name="adsterra_code_article" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="<script>...</script>">{{ setting('adsterra_code_article', '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sidebar Code <span class="text-gray-400 font-normal">(post page sidebar, above Related Posts)</span></label>
        <textarea name="adsterra_code_sidebar" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="<script>...</script>">{{ setting('adsterra_code_sidebar', '') }}</textarea>
    </div>
</div>

<div class="flex justify-end mt-6">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Ad Settings</button>
</div>
</form>
@endsection
