@extends('admin.settings.layout')
@section('settings-title', 'Social Media')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'social') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Social Media Links</h2>
    @php
    $socials = [
        'facebook_url'  => ['label' => 'Facebook',  'color' => '#1877F2', 'ph' => 'https://facebook.com/yourpage'],
        'youtube_url'   => ['label' => 'YouTube',   'color' => '#FF0000', 'ph' => 'https://youtube.com/@yourchannel'],
        'instagram_url' => ['label' => 'Instagram', 'color' => '#E4405F', 'ph' => 'https://instagram.com/yourpage'],
        'linkedin_url'  => ['label' => 'LinkedIn',  'color' => '#0A66C2', 'ph' => 'https://linkedin.com/company/yourpage'],
        'twitter_url'   => ['label' => 'X (Twitter)','color'=> '#000000', 'ph' => 'https://x.com/yourhandle'],
        'tiktok_url'    => ['label' => 'TikTok',    'color' => '#010101', 'ph' => 'https://tiktok.com/@yourpage'],
        'pinterest_url' => ['label' => 'Pinterest', 'color' => '#E60023', 'ph' => 'https://pinterest.com/yourpage'],
        'whatsapp_link' => ['label' => 'WhatsApp (Chat Link)', 'color' => '#25D366', 'ph' => 'https://wa.me/8801700000000'],
        'messenger_link' => ['label' => 'Messenger (Chat Link)', 'color' => '#0084FF', 'ph' => 'https://m.me/yourpage'],
    ];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($socials as $key => $social)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span class="inline-block w-2.5 h-2.5 rounded-full mr-1" style="background-color: {{ $social['color'] }}"></span>
                {{ $social['label'] }}
            </label>
            <input type="url" name="{{ $key }}" value="{{ setting($key, '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="{{ $social['ph'] }}">
        </div>
        @endforeach
    </div>
</div>

{{-- The floating widget (layouts/app.blade.php) reads whatsapp_link/messenger_link/
     linkedin_url straight from the Social Media Links card above — one link field per
     channel, entered once, no duplicate WhatsApp-number field to keep in sync. Each
     channel's floating_{channel}_enabled toggle below is independent of that link, so
     turning an icon off in the widget doesn't clear (or require re-typing) its link. --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Floating Contact Widget</h2>
    <p class="text-sm text-gray-500">
        A small stack of chat buttons pinned to the left edge of every storefront page.
        Turn the widget on, then pick exactly which icons to show — independently of
        each other, so you can run just one, two, three, or all four.
    </p>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="floating_widget_enabled" value="0">
        <input type="checkbox" name="floating_widget_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('floating_widget_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Show the floating contact widget</span>
    </label>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            <span class="inline-block w-2.5 h-2.5 rounded-full mr-1" style="background-color: #26A5E4"></span>
            Telegram (Chat Link)
        </label>
        <input type="url" name="telegram_link" value="{{ setting('telegram_link', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
               placeholder="https://t.me/yourusername">
    </div>
    <p class="text-xs text-gray-400">
        WhatsApp, Messenger and LinkedIn reuse the links entered in Social Media Links
        above — fill those in too if you want to turn those icons on below.
    </p>

    <div class="border-t pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach([
            ['key' => 'floating_whatsapp_enabled', 'label' => 'WhatsApp', 'color' => '#25D366'],
            ['key' => 'floating_telegram_enabled', 'label' => 'Telegram', 'color' => '#26A5E4'],
            ['key' => 'floating_messenger_enabled', 'label' => 'Messenger', 'color' => '#00B2FF'],
            ['key' => 'floating_linkedin_enabled', 'label' => 'LinkedIn', 'color' => '#0A66C2'],
        ] as $toggle)
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="{{ $toggle['key'] }}" value="0">
            <input type="checkbox" name="{{ $toggle['key'] }}" value="1" class="rounded text-orange-600"
                   @checked(setting($toggle['key'], '1') == '1')>
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: {{ $toggle['color'] }}"></span>
            <span class="text-sm text-gray-700">Show {{ $toggle['label'] }} icon</span>
        </label>
        @endforeach
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Social Links</button>
</div>
</form>
@endsection
