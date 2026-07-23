@extends('admin.settings.layout')
@section('settings-title', 'Theme & Design')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'theme') }}">
@csrf @method('PATCH')

<div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
    Brand colors (Primary, Secondary, Accent, Text) moved to <a href="{{ route('admin.settings.show', 'branding') }}" class="font-semibold underline">Settings → Branding</a> — edit them there.
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Typography</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
            <select name="font_family" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @foreach([
                    'Inter, sans-serif' => 'Inter',
                    'Roboto, sans-serif' => 'Roboto',
                    'Poppins, sans-serif' => 'Poppins',
                    'Nunito, sans-serif' => 'Nunito',
                    'Open Sans, sans-serif' => 'Open Sans',
                    'Lato, sans-serif' => 'Lato',
                    'system-ui, sans-serif' => 'System Default',
                ] as $value => $name)
                <option value="{{ $value }}" @selected(setting('font_family','Inter, sans-serif')===$value)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Button Style</label>
            <select name="button_style" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="rounded" @selected(setting('button_style','rounded')==='rounded')>Rounded (default)</option>
                <option value="square" @selected(setting('button_style','rounded')==='square')>Square Corners</option>
                <option value="pill" @selected(setting('button_style','rounded')==='pill')>Pill Shape</option>
            </select>
        </div>
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
