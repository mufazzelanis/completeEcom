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

    {{-- Main trigger (layouts/app.blade.php) — the single red bubble all the channels
         above fan out from. Its own color/text, separate from any one channel's brand
         color since it represents all of them. --}}
    <div class="border-t pt-4"
         x-data="{
            label: {{ Js::from(setting('floating_widget_label', 'Chat with us')) }},
            greeting: {{ Js::from(setting('floating_widget_greeting', "👋 Hi there! Need help finding something? We're online — chat with us.")) }},
            useCustomColor: {{ setting('floating_widget_color_from') ? 'true' : 'false' }},
            from: {{ Js::from(setting('floating_widget_color_from', '#ef4444')) }},
            to: {{ Js::from(setting('floating_widget_color_to', '#e11d48')) }},
         }">
        <p class="text-sm font-semibold text-gray-800 mb-3">Main Trigger Button</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text <span class="text-xs text-gray-400 font-normal">(desktop hover label)</span></label>
                <input type="text" name="floating_widget_label" x-model="label" maxlength="30" placeholder="Chat with us"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Greeting Message <span class="text-xs text-gray-400 font-normal">(one-time popup)</span></label>
                <input type="text" name="floating_widget_greeting" x-model="greeting" maxlength="150"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>

        <label class="flex items-center gap-2 cursor-pointer mb-3">
            <input type="checkbox" x-model="useCustomColor" class="rounded text-orange-600">
            <span class="text-sm font-medium text-gray-700">Custom Button Color (RGB)</span>
        </label>
        <input type="hidden" name="floating_widget_color_enabled" :value="useCustomColor ? '1' : '0'">

        <div x-show="useCustomColor" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient Start</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="floating_widget_color_from" x-model="from" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="from" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Gradient End</label>
                <div class="flex items-center gap-1.5">
                    <input type="color" name="floating_widget_color_to" x-model="to" class="h-9 w-10 flex-shrink-0 rounded border cursor-pointer">
                    <input type="text" x-model="to" class="w-0 flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono bg-gray-50">
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-3" x-show="!useCustomColor">Uses the default red. Turn this on to pick your own.</p>

        {{-- Live preview — same markup/shape as the real trigger button. --}}
        <div class="bg-gray-50 border rounded-xl p-4 flex items-center gap-4">
            <span class="text-xs text-gray-400 flex-shrink-0">Preview:</span>
            <button type="button" tabindex="-1"
                class="relative h-12 rounded-full text-white shadow-lg flex items-center justify-center gap-2 px-3.5"
                :style="useCustomColor ? { backgroundImage: `linear-gradient(to bottom right, ${from}, ${to})` } : { backgroundImage: 'linear-gradient(to bottom right, #ef4444, #e11d48)' }">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h16a2 2 0 012 2v10a2 2 0 01-2 2H8l-4 4V6a2 2 0 012-2z"/></svg>
                <span class="text-sm font-bold" x-text="label"></span>
            </button>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Social Links</button>
</div>
</form>
@endsection
