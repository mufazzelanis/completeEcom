@extends('admin.settings.layout')
@section('settings-title', 'Maintenance')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'maintenance') }}" enctype="multipart/form-data">
@csrf @method('PATCH')

@php $isOn = setting('maintenance_mode','0') === '1'; @endphp

@if($isOn)
<div class="bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 flex items-center gap-3">
    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
    </svg>
    <span class="text-sm font-semibold text-amber-800">⚠ Maintenance mode is currently ON. Your site is not visible to the public.</span>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Maintenance Mode</h2>
    <label class="flex items-center gap-3 cursor-pointer">
        <input type="hidden" name="maintenance_mode" value="0">
        <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" class="rounded text-amber-600"
               @checked($isOn)>
        <div>
            <span class="text-sm font-medium text-gray-700">Enable Maintenance Mode</span>
            <p class="text-xs text-gray-400">When enabled, visitors see the maintenance page. Admins can still log in.</p>
        </div>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Message</label>
        <textarea name="maintenance_message" rows="3"
                  class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                  placeholder="We'll be back shortly. Thank you for your patience.">{{ setting('maintenance_message', 'We are currently performing scheduled maintenance. We\'ll be back shortly!') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Expected Back Time</label>
        <input type="text" name="maintenance_back_time" value="{{ setting('maintenance_back_time', '') }}"
               class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
               placeholder="e.g. 2 hours, 30 minutes, Sunday 10:00 PM">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Page Banner Image</label>
        @php $bannerUrl = setting_file_url('maintenance_banner'); @endphp
        @if($bannerUrl)
        <img src="{{ $bannerUrl }}" alt="Banner" class="h-24 rounded-lg mb-2 object-cover">
        @endif
        <input type="file" name="maintenance_banner" accept="image/*"
               class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Allowed IPs (bypass maintenance)</label>
        <textarea name="maintenance_allowed_ips" rows="2"
                  class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                  placeholder="One IP per line. Leave blank to block all.">{{ setting('maintenance_allowed_ips', '') }}</textarea>
        <p class="text-xs text-gray-400 mt-1">Your IP: {{ request()->ip() }}</p>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 {{ $isOn ? 'bg-amber-500 hover:bg-amber-600' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white rounded-lg text-sm font-semibold transition">
        {{ $isOn ? 'Update Maintenance Settings' : 'Save Maintenance Settings' }}
    </button>
</div>
</form>
@endsection
