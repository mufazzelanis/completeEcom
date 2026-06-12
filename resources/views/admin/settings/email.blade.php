@extends('admin.settings.layout')
@section('settings-title', 'Email & SMTP')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'email') }}">
@csrf @method('PATCH')

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">SMTP Configuration</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mail Driver</label>
            <select name="mail_mailer" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                @foreach(['smtp','log','sendmail','mailgun','ses','postmark'] as $m)
                <option value="{{ $m }}" @selected(setting('mail_mailer',env('MAIL_MAILER','log'))===$m)>{{ strtoupper($m) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
            <input type="text" name="mail_host" value="{{ setting('mail_host', env('MAIL_HOST', 'smtp.gmail.com')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="smtp.gmail.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
            <input type="number" name="mail_port" value="{{ setting('mail_port', env('MAIL_PORT','587')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="587">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
            <select name="mail_encryption" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="tls" @selected(setting('mail_encryption','tls')==='tls')>TLS</option>
                <option value="ssl" @selected(setting('mail_encryption','tls')==='ssl')>SSL</option>
                <option value="" @selected(setting('mail_encryption','tls')==='')>None</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Username</label>
            <input type="text" name="mail_username" value="{{ setting('mail_username', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="your@gmail.com" autocomplete="off">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password / App Password</label>
            <input type="password" name="mail_password" value="{{ setting('mail_password', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Sender Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
            <input type="text" name="mail_from_name" value="{{ setting('mail_from_name', setting('site_name','ShopVista')) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Email Address</label>
            <input type="email" name="mail_from_address" value="{{ setting('mail_from_address', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="noreply@yourstore.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Email</label>
            <input type="email" name="mail_reply_to" value="{{ setting('mail_reply_to', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="support@yourstore.com">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notification Email</label>
            <input type="email" name="admin_notification_email" value="{{ setting('admin_notification_email', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="admin@yourstore.com">
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b mb-4">Test Email</h2>
    <form method="POST" action="{{ route('admin.settings.test-email') }}" class="flex gap-3">
        @csrf
        <input type="email" name="test_email" required placeholder="Send test to this email"
               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">
            Send Test
        </button>
    </form>
    <p class="text-xs text-gray-400 mt-2">Save settings first before testing.</p>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Email Settings</button>
</div>
</form>
@endsection
