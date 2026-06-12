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
            <select name="header_layout" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="default" @selected(setting('header_layout','default')==='default')>Default (Logo Left)</option>
                <option value="centered" @selected(setting('header_layout','default')==='centered')>Centered Logo</option>
                <option value="minimal" @selected(setting('header_layout','default')==='minimal')>Minimal</option>
            </select>
        </div>
        <div class="flex items-end gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="sticky_header" value="0">
                <input type="checkbox" name="sticky_header" value="1" class="rounded text-indigo-600"
                       @checked(setting('sticky_header','1') == '1')>
                <span class="text-sm text-gray-700">Sticky Header</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="top_bar_enabled" value="0">
                <input type="checkbox" name="top_bar_enabled" value="1" class="rounded text-indigo-600"
                       @checked(setting('top_bar_enabled','0') == '1')>
                <span class="text-sm text-gray-700">Enable Top Bar</span>
            </label>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Top Bar Contact Info</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone (Top Bar)</label>
            <input type="text" name="topbar_phone" value="{{ setting('topbar_phone', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="+880 1700-000000">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email (Top Bar)</label>
            <input type="email" name="topbar_email" value="{{ setting('topbar_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="support@example.com">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Top Bar Text (Left)</label>
            <input type="text" name="topbar_text" value="{{ setting('topbar_text', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="Free shipping on orders over ৳999">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Announcement Bar</h2>
    <label class="flex items-center gap-2 cursor-pointer mb-3">
        <input type="hidden" name="announcement_enabled" value="0">
        <input type="checkbox" name="announcement_enabled" value="1" class="rounded text-indigo-600"
               @checked(setting('announcement_enabled','0') == '1')>
        <span class="text-sm text-gray-700">Show Announcement Bar</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Announcement Text</label>
        <input type="text" name="announcement_text" value="{{ setting('announcement_text', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
               placeholder="🎉 Sale! Use code SAVE10 for 10% off all orders.">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bar Background Color</label>
            <input type="color" name="announcement_bg" value="{{ setting('announcement_bg', '#6366f1') }}"
                   class="h-9 w-full rounded border cursor-pointer">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
            <input type="color" name="announcement_color" value="{{ setting('announcement_color', '#ffffff') }}"
                   class="h-9 w-full rounded border cursor-pointer">
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Header</button>
</div>
</form>
@endsection
