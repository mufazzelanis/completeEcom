@extends('admin.settings.layout')
@section('settings-title', 'SMS & WhatsApp')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'sms') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">SMS Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SMS Driver</label>
            <select name="sms_driver" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="log" @selected(setting('sms_driver','log')==='log')>Log (Development)</option>
                <option value="twilio" @selected(setting('sms_driver','log')==='twilio')>Twilio</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notification Phone</label>
            <input type="text" name="admin_notification_phone" value="{{ setting('admin_notification_phone', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="+8801700000000">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Twilio Credentials</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account SID</label>
            <input type="text" name="twilio_sid" value="{{ setting('twilio_sid', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" autocomplete="off">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Auth Token</label>
            <input type="password" name="twilio_token" value="{{ setting('twilio_token', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Number (SMS)</label>
            <input type="text" name="twilio_from" value="{{ setting('twilio_from', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="+1234567890">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">WhatsApp Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Driver</label>
            <select name="whatsapp_driver" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                <option value="log" @selected(setting('whatsapp_driver','log')==='log')>Log (Development)</option>
                <option value="twilio" @selected(setting('whatsapp_driver','log')==='twilio')>Twilio WhatsApp</option>
                <option value="meta" @selected(setting('whatsapp_driver','log')==='meta')>Meta Cloud API</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Twilio WhatsApp From</label>
            <input type="text" name="twilio_whatsapp_from" value="{{ setting('twilio_whatsapp_from', 'whatsapp:+14155238886') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="whatsapp:+14155238886">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Access Token</label>
            <input type="password" name="meta_whatsapp_token" value="{{ setting('meta_whatsapp_token', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="EAAxxxxxxx" autocomplete="new-password">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Phone Number ID</label>
            <input type="text" name="meta_whatsapp_phone_id" value="{{ setting('meta_whatsapp_phone_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"
                   placeholder="123456789012345">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">OTP Settings</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="otp_enabled" value="0">
            <input type="checkbox" name="otp_enabled" value="1" class="rounded text-orange-600"
                   @checked(setting('otp_enabled','0') == '1')>
            <span class="text-sm text-gray-700">Enable OTP Login</span>
        </label>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OTP Expiry (minutes)</label>
            <input type="number" name="otp_expiry" value="{{ setting('otp_expiry', '10') }}"
                   min="1" max="60"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">OTP Length</label>
            <select name="otp_length" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                @foreach([4,5,6,8] as $l)
                <option value="{{ $l }}" @selected((int)setting('otp_length','6')===$l)>{{ $l }} digits</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save SMS/WhatsApp</button>
</div>
</form>
@endsection
