@extends('admin.settings.layout')
@section('settings-title', 'Branding')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'branding') }}" enctype="multipart/form-data">
@csrf @method('PATCH')

@php
$logos = [
    'site_logo'    => ['label' => 'Site Logo',        'desc' => 'Main logo used in the header (recommended: 200×60 px)'],
    'dark_logo'    => ['label' => 'Dark Logo',         'desc' => 'Logo for dark backgrounds (recommended: 200×60 px)'],
    'favicon'      => ['label' => 'Favicon',           'desc' => '16×16 or 32×32 px .ico or .png file'],
    'footer_logo'  => ['label' => 'Footer Logo',       'desc' => 'Logo shown in the footer (recommended: 160×50 px)'],
    'email_logo'   => ['label' => 'Email Logo',        'desc' => 'Logo used in email templates (recommended: 180×55 px)'],
    'invoice_logo' => ['label' => 'Invoice Logo',      'desc' => 'Logo printed on PDF invoices (recommended: 200×60 px)'],
    'login_logo'   => ['label' => 'Login Page Logo',   'desc' => 'Logo on the login/register page (recommended: 160×50 px)'],
];
@endphp

<div class="bg-white rounded-xl shadow-sm border p-6">
    <h2 class="text-base font-semibold text-gray-900 pb-3 border-b mb-5">Logo & Favicon</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($logos as $key => $meta)
        @php $currentUrl = setting_file_url($key); @endphp
        <div class="border rounded-xl p-4 space-y-3">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $meta['label'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $meta['desc'] }}</p>
            </div>
            @if($currentUrl)
            <div class="flex items-center gap-3">
                <img src="{{ $currentUrl }}" alt="{{ $meta['label'] }}"
                     class="h-12 max-w-[140px] object-contain rounded border bg-gray-50 p-1">
                <span class="text-xs text-green-600 font-medium">Uploaded</span>
            </div>
            @else
            <div class="h-12 flex items-center justify-center rounded border border-dashed border-gray-200 bg-gray-50">
                <span class="text-xs text-gray-400">No file uploaded</span>
            </div>
            @endif
            <input type="file" name="{{ $key }}" accept="image/*"
                   class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Brand Colors</h2>
    <p class="text-sm text-gray-500">Use the Theme & Design section for full color customization. Quick presets here:</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="primary_color" value="{{ setting('primary_color', '#6366f1') }}"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" value="{{ setting('primary_color', '#6366f1') }}" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
            <div class="flex items-center gap-2">
                <input type="color" name="secondary_color" value="{{ setting('secondary_color', '#ec4899') }}"
                       class="h-9 w-16 rounded border cursor-pointer">
                <input type="text" value="{{ setting('secondary_color', '#ec4899') }}" readonly
                       class="flex-1 border rounded px-2 py-1.5 text-xs text-gray-600">
            </div>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Branding</button>
</div>
</form>
@endsection
