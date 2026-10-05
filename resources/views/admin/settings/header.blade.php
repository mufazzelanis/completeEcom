@extends('admin.settings.layout')
@section('settings-title', 'Header Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'header') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Header Layout</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Header Layout</label>
            <select name="header_layout" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="default" @selected(setting('header_layout','default')==='default')>Default (Logo Left)</option>
                <option value="centered" @selected(setting('header_layout','default')==='centered')>Centered Logo</option>
                <option value="minimal" @selected(setting('header_layout','default')==='minimal')>Minimal</option>
            </select>
        </div>
        <div class="flex items-end gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="sticky_header" value="0">
                <input type="checkbox" name="sticky_header" value="1" class="rounded text-orange-600"
                       @checked(setting('sticky_header','1') == '1')>
                <span class="text-sm text-gray-700">Sticky Header</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="top_bar_enabled" value="0">
                <input type="checkbox" name="top_bar_enabled" value="1" class="rounded text-orange-600"
                       @checked(setting('top_bar_enabled','0') == '1')>
                <span class="text-sm text-gray-700">Enable Top Bar</span>
            </label>
            <p class="text-xs text-gray-400 -mt-2 md:col-span-2">Note: the top bar also shows automatically once you fill in any field below (Phone, Email, or Text), even if this box is left unchecked.</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Top Bar Contact Info</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone (Top Bar)</label>
            <input type="text" name="topbar_phone" value="{{ setting('topbar_phone', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="+880 1700-000000">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email (Top Bar)</label>
            <input type="email" name="topbar_email" value="{{ setting('topbar_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="support@example.com">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Top Bar Text (Left)</label>
            <input type="text" name="topbar_text" value="{{ setting('topbar_text', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="Free shipping on orders over ৳999">
        </div>
    </div>
</div>

{{-- Announcement Bar — previously a plain checkbox + bare <input type=text>, which had two
     problems: (1) the text field is sanitized through HTMLPurifier on save (SettingController),
     and until the 'inline' profile was added (config/purifier.php) that auto-wrapped even a
     one-line banner in a literal <p>...</p> — showing up as raw "<p>" text next time this plain
     (non-WYSIWYG) field was reopened; (2) there was no way to see how the colors/text would
     actually look without saving and visiting the storefront. The live preview below renders the
     exact same markup/animation as the real bar (layouts/app.blade.php) so both problems are
     fixed by the same change: what you see here is what ships, tags and all. --}}
<div class="relative bg-white rounded-xl shadow-sm border overflow-hidden"
     x-data="{
        enabled: {{ setting('announcement_enabled','0') == '1' ? 'true' : 'false' }},
        text: @js(setting('announcement_text', '')),
        bg: @js(setting('announcement_bg', '#6366f1')),
        color: @js(setting('announcement_color', '#ffffff')),
     }">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-orange-500 via-pink-500 to-indigo-500"></div>

    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between gap-4 pb-3 border-b">
            <div class="flex items-center gap-3">
                <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-gradient-to-br from-orange-500 to-pink-500 flex items-center justify-center text-white shadow-sm shadow-orange-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </span>
                <h2 class="text-base font-semibold text-gray-900">Announcement Bar</h2>
            </div>

            {{-- Real checkbox underneath (native form field, keyboard-operable) — the switch
                 look is just peer-* styling layered on top, same hidden-then-checkbox "0 unless
                 checked" pairing every other boolean setting on this page already uses. --}}
            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                <input type="hidden" name="announcement_enabled" value="0">
                <input type="checkbox" name="announcement_enabled" value="1" x-model="enabled" class="sr-only peer">
                <span class="w-11 h-6 rounded-full bg-gray-200 peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-pink-500 transition-colors duration-300"></span>
                <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-transform duration-300 peer-checked:translate-x-5"></span>
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Announcement Text</label>
            <input type="text" name="announcement_text" x-model="text"
                   class="w-full border rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition"
                   placeholder="🎉 Sale! Use code SAVE10 for 10% off all orders.">
            <p class="text-xs text-gray-400 mt-1.5">Plain text and emoji work best. For emphasis you can also use <code class="bg-gray-100 px-1 rounded">&lt;b&gt;bold&lt;/b&gt;</code> or a link: <code class="bg-gray-100 px-1 rounded">&lt;a href="..."&gt;text&lt;/a&gt;</code> — see exactly how it'll look in the preview below.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Bar Background Color</label>
                <div class="flex items-center gap-2 border rounded-lg pl-1.5 pr-3 py-1.5 focus-within:ring-2 focus-within:ring-orange-500 focus-within:border-orange-500 transition">
                    <input type="color" name="announcement_bg" x-model="bg" class="h-7 w-9 flex-shrink-0 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="bg" maxlength="7" class="flex-1 min-w-0 text-sm font-mono text-gray-600 bg-transparent focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Text Color</label>
                <div class="flex items-center gap-2 border rounded-lg pl-1.5 pr-3 py-1.5 focus-within:ring-2 focus-within:ring-orange-500 focus-within:border-orange-500 transition">
                    <input type="color" name="announcement_color" x-model="color" class="h-7 w-9 flex-shrink-0 rounded cursor-pointer border-0 bg-transparent p-0">
                    <input type="text" x-model="color" maxlength="7" class="flex-1 min-w-0 text-sm font-mono text-gray-600 bg-transparent focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Live preview — identical markup/animation (pulsing dot + sheen sweep) to the real
             storefront bar, updating as any field above changes. --}}
        <div>
            <p class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Live Preview
            </p>
            <div class="rounded-lg overflow-hidden ring-1 ring-gray-100 transition-opacity duration-300" :class="enabled ? 'opacity-100' : 'opacity-40'">
                <div class="relative overflow-hidden text-sm py-2 text-center font-semibold px-10" :style="`background: ${bg}; color: ${color};`">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0"></div>
                    <span class="relative inline-flex items-center gap-2">
                        <span class="relative flex h-2 w-2 flex-shrink-0">
                            <span class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping" :style="`background: ${color};`"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2" :style="`background: ${color};`"></span>
                        </span>
                        <span x-show="text" x-html="text"></span>
                        <span x-show="!text" class="opacity-70">Your announcement text will appear here</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Header</button>
</div>
</form>
@endsection
