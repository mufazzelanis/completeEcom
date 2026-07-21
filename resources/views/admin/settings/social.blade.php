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

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Social Links</button>
</div>
</form>
@endsection
