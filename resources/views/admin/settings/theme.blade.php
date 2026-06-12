@extends('admin.settings.layout')
@section('settings-title', 'Theme & Design')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'theme') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Color Scheme</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
        $colors = [
            'primary_color'    => ['label' => 'Primary Color',    'default' => '#6366f1'],
            'secondary_color'  => ['label' => 'Secondary Color',  'default' => '#ec4899'],
            'accent_color'     => ['label' => 'Accent Color',     'default' => '#f59e0b'],
            'text_color'       => ['label' => 'Body Text Color',  'default' => '#111827'],
        ];
        @endphp
        @foreach($colors as $key => $meta)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $meta['label'] }}</label>
            <div class="flex items-center gap-2">
                <input type="color" name="{{ $key }}" value="{{ setting($key, $meta['default']) }}"
                       class="h-9 w-14 rounded border cursor-pointer flex-shrink-0">
                <input type="text" value="{{ setting($key, $meta['default']) }}" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600 font-mono">
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Typography</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
            <select name="font_family" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
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
            <select name="button_style" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="rounded" @selected(setting('button_style','rounded')==='rounded')>Rounded (default)</option>
                <option value="square" @selected(setting('button_style','rounded')==='square')>Square Corners</option>
                <option value="pill" @selected(setting('button_style','rounded')==='pill')>Pill Shape</option>
            </select>
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
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500"
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
                  class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-indigo-500"
                  placeholder="// Your custom JS here
console.log('ShopVista loaded');">{{ setting('custom_js', '') }}</textarea>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Theme</button>
</div>
</form>
@endsection
