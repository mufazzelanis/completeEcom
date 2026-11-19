@extends('admin.settings.layout')
@section('settings-title', 'Theme & Design')

{{-- Every candidate font (not just the two currently saved) so the live preview below
     actually shows each option's real typeface the instant it's picked, not just a
     generic fallback that happens to look the same for every selection. --}}
@push('styles')
@php
    $allFontFamilies = collect([
        'Inter', 'Plus+Jakarta+Sans', 'Manrope', 'DM+Sans', 'Outfit', 'Poppins', 'Urbanist',
        'Work+Sans', 'Montserrat', 'Nunito', 'Roboto', 'Open+Sans', 'Lato',
        'Hind+Siliguri', 'Noto+Sans+Bengali', 'Baloo+Da+2', 'Tiro+Bangla', 'Atma', 'Mina',
    ])->map(fn ($f) => "family={$f}:wght@400;500;600;700")->implode('&');
@endphp
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $allFontFamilies }}&display=swap">
@endpush

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'theme') }}">
@csrf @method('PATCH')

<div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
    Brand colors (Primary, Secondary, Accent, Text) moved to <a href="{{ route('admin.settings.show', 'branding') }}" class="font-semibold underline">Settings → Branding</a> — edit them there.
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-5"
     x-data="{
        en: @js(setting('font_family', 'Inter, sans-serif')),
        bn: @js(setting('font_family_bn', 'Hind Siliguri, sans-serif')),
        name(v) { return v.split(',')[0]; },
     }">
    <div>
        <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Typography</h2>
        <p class="text-xs text-gray-400 mt-2">
            Two fonts, used together — this site shows English and Bengali side by side
            everywhere (product names, UI text, blog posts, landing pages), not behind a
            language switch. Browsers pick the right font per character automatically, so
            pick one of each below and both scripts render correctly, everywhere, with no
            further setup.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">English Font</label>
            <select name="font_family" x-model="en" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @foreach([
                    'Inter, sans-serif' => 'Inter — clean, modern (default)',
                    'Plus Jakarta Sans, sans-serif' => 'Plus Jakarta Sans — friendly & trendy',
                    'Manrope' => 'Manrope — crisp, premium feel',
                    'DM Sans, sans-serif' => 'DM Sans — warm, geometric',
                    'Outfit, sans-serif' => 'Outfit — bold, confident',
                    'Poppins, sans-serif' => 'Poppins — rounded, approachable',
                    'Urbanist, sans-serif' => 'Urbanist — light, airy',
                    'Work Sans, sans-serif' => 'Work Sans — versatile, readable',
                    'Montserrat, sans-serif' => 'Montserrat — strong headings',
                    'Nunito, sans-serif' => 'Nunito — soft, friendly',
                    'Roboto, sans-serif' => 'Roboto — neutral, familiar',
                    'Open Sans, sans-serif' => 'Open Sans — classic, safe',
                    'Lato, sans-serif' => 'Lato — warm, professional',
                    'system-ui, sans-serif' => 'System Default (fastest load)',
                ] as $value => $name)
                <option value="{{ $value }}" @selected(setting('font_family','Inter, sans-serif')===$value)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bengali Font</label>
            <select name="font_family_bn" x-model="bn" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @foreach([
                    'Hind Siliguri, sans-serif' => 'Hind Siliguri — clean, most popular (default)',
                    'Noto Sans Bengali, sans-serif' => 'Noto Sans Bengali — modern, excellent coverage',
                    'Baloo Da 2, sans-serif' => 'Baloo Da 2 — friendly, rounded, bold',
                    'Tiro Bangla, serif' => 'Tiro Bangla — elegant serif, premium feel',
                    'Atma, cursive' => 'Atma — warm, handwritten accent',
                    'Mina, sans-serif' => 'Mina — compact, modern',
                    'system-ui, sans-serif' => 'System Default (fastest load)',
                ] as $value => $name)
                <option value="{{ $value }}" @selected(setting('font_family_bn','Hind Siliguri, sans-serif')===$value)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide mb-2">Live Preview</p>
        <p class="text-lg text-gray-800 leading-relaxed" :style="{ fontFamily: name(en) + ', ' + name(bn) + ', sans-serif' }">
            Fresh Deals Every Day — আজই অর্ডার করুন, ফ্রি ডেলিভারি পান!
        </p>
        <p class="text-sm text-gray-500 mt-1" :style="{ fontFamily: name(en) + ', ' + name(bn) + ', sans-serif' }">
            ৳1,250.00 · Add to Cart · কার্টে যোগ করুন
        </p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Button Style</label>
        <select name="button_style" class="w-full md:w-1/2 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <option value="rounded" @selected(setting('button_style','rounded')==='rounded')>Rounded (default)</option>
            <option value="square" @selected(setting('button_style','rounded')==='square')>Square Corners</option>
            <option value="pill" @selected(setting('button_style','rounded')==='pill')>Pill Shape</option>
        </select>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Layout & Appearance</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Corner Roundness</label>
            <select name="border_radius" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="sharp" @selected(setting('border_radius','soft')==='sharp')>Sharp</option>
                <option value="soft" @selected(setting('border_radius','soft')==='soft')>Soft (default)</option>
                <option value="round" @selected(setting('border_radius','soft')==='round')>Round</option>
                <option value="xround" @selected(setting('border_radius','soft')==='xround')>Extra Round</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Applies to cards, images, and inputs site-wide. Circular elements (avatars, badges) stay circular.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Card Shadow</label>
            <select name="shadow_style" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="none" @selected(setting('shadow_style','soft')==='none')>None (flat)</option>
                <option value="soft" @selected(setting('shadow_style','soft')==='soft')>Soft (default)</option>
                <option value="medium" @selected(setting('shadow_style','soft')==='medium')>Medium</option>
                <option value="strong" @selected(setting('shadow_style','soft')==='strong')>Strong</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Content Width</label>
            <select name="container_width" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="compact" @selected(setting('container_width','standard')==='compact')>Compact (1040px)</option>
                <option value="standard" @selected(setting('container_width','standard')==='standard')>Standard (1200px, default)</option>
                <option value="wide" @selected(setting('container_width','standard')==='wide')>Wide (1400px)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dark Mode Default</label>
            <select name="dark_mode_default" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="system" @selected(setting('dark_mode_default','system')==='system')>Match Visitor's Device (default)</option>
                <option value="light" @selected(setting('dark_mode_default','system')==='light')>Always Light</option>
                <option value="dark" @selected(setting('dark_mode_default','system')==='dark')>Always Dark</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Only affects first-time visitors — anyone who has already toggled the theme keeps their own choice.</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Custom Code</h2>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Custom CSS
            <span class="text-xs font-normal text-gray-400 ml-1">(injected in &lt;head&gt;)</span>
        </label>
        <textarea name="custom_css" rows="8"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="/* Your custom CSS here */
:root {
  --color-primary: #6366f1;
}">{{ setting('custom_css', '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Custom JavaScript
            <span class="text-xs font-normal text-gray-400 ml-1">(injected before &lt;/body&gt;)</span>
        </label>
        <textarea name="custom_js" rows="8"
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="// Your custom JS here
console.log('ShopVista loaded');">{{ setting('custom_js', '') }}</textarea>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Theme</button>
</div>
</form>
@endsection
