@extends('admin.settings.layout')
@section('settings-title', 'Security')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'security') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Google reCAPTCHA</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="recaptcha_enabled" value="0">
        <input type="checkbox" name="recaptcha_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('recaptcha_enabled','0') == '1')>
        <span class="text-sm text-gray-700">Enable reCAPTCHA on forms</span>
    </label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">reCAPTCHA Version</label>
            <select name="recaptcha_version" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="v2" @selected(setting('recaptcha_version','v2')==='v2')>v2 (Checkbox)</option>
                <option value="v3" @selected(setting('recaptcha_version','v2')==='v3')>v3 (Invisible)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
            <input type="text" name="recaptcha_site_key" value="{{ setting('recaptcha_site_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="6LcXXXXXXXXXXXX" autocomplete="off">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
            <input type="password" name="recaptcha_secret_key" value="{{ setting('recaptcha_secret_key', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Login Security</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Login Attempts</label>
            <input type="number" name="login_max_attempts" value="{{ setting('login_max_attempts', '5') }}"
                   min="1" max="20"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lockout Duration (minutes)</label>
            <input type="number" name="login_lockout_minutes" value="{{ setting('login_lockout_minutes', '15') }}"
                   min="1"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="two_factor_enabled" value="0">
            <input type="checkbox" name="two_factor_enabled" value="1" class="rounded text-orange-600"
                   @checked(setting('two_factor_enabled','0') == '1')>
            <span class="text-sm text-gray-700">Require 2FA for Admin</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="force_https" value="0">
            <input type="checkbox" name="force_https" value="1" class="rounded text-orange-600"
                   @checked(setting('force_https','0') == '1')>
            <span class="text-sm text-gray-700">Force HTTPS</span>
        </label>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">IP Restrictions</h2>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="ip_restriction_enabled" value="0">
        <input type="checkbox" name="ip_restriction_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('ip_restriction_enabled','0') == '1')>
        <span class="text-sm text-gray-700">Enable Admin IP Whitelist</span>
    </label>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Allowed IPs (one per line)</label>
        <textarea name="allowed_ips" rows="4"
                  class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                  placeholder="127.0.0.1&#10;192.168.1.1&#10;203.0.113.0/24">{{ setting('allowed_ips', '') }}</textarea>
        <p class="text-xs text-gray-400 mt-1">Your current IP: {{ request()->ip() }}</p>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Security</button>
</div>
</form>
@endsection
