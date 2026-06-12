@extends('layouts.admin')
@section('title', 'Notification Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Notification Settings</h1>
        <a href="{{ route('admin.notifications.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
    </div>

    {{-- Channel Status --}}
    @php
    $channelStatus = [
        'Email'    => ['driver' => config('mail.default'), 'configured' => filled(config('mail.from.address')), 'color' => 'blue'],
        'SMS'      => ['driver' => config('notifications.sms.driver'), 'configured' => filled(config('notifications.sms.sid')), 'color' => 'green'],
        'Push'     => ['driver' => config('notifications.push.driver'), 'configured' => filled(config('notifications.push.server_key')), 'color' => 'purple'],
        'WhatsApp' => ['driver' => config('notifications.whatsapp.driver'), 'configured' => config('notifications.whatsapp.driver') === 'log' || filled(config('notifications.whatsapp.meta_token')) || filled(config('notifications.sms.sid')), 'color' => 'emerald'],
    ];
    @endphp

    <div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Channel Configuration Status</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($channelStatus as $name => $info)
            <div class="flex items-start gap-3 p-4 rounded-lg border {{ $info['configured'] ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                <div class="mt-0.5">
                    @if($info['configured'])
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Driver: <code class="bg-gray-100 px-1 rounded">{{ $info['driver'] }}</code></p>
                    <p class="text-xs mt-0.5 {{ $info['configured'] ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $info['configured'] ? 'Configured' : 'Missing credentials in .env' }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ENV Variables Reference --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Required .env Variables</h2>
        <div class="space-y-5">
            <div>
                <h3 class="text-sm font-medium text-blue-700 mb-2">Email (Laravel Mail)</h3>
                <pre class="bg-gray-900 text-green-300 text-xs rounded-lg p-4 overflow-x-auto">MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=app_password
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="ShopVista"
ADMIN_NOTIFICATION_EMAIL=admin@yourstore.com</pre>
            </div>
            <div>
                <h3 class="text-sm font-medium text-green-700 mb-2">SMS + WhatsApp via Twilio</h3>
                <pre class="bg-gray-900 text-green-300 text-xs rounded-lg p-4 overflow-x-auto">SMS_DRIVER=twilio
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=your_auth_token
TWILIO_FROM=+1234567890
WHATSAPP_DRIVER=twilio
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
ADMIN_NOTIFICATION_PHONE=+8801700000000</pre>
            </div>
            <div>
                <h3 class="text-sm font-medium text-purple-700 mb-2">Push via Firebase FCM</h3>
                <pre class="bg-gray-900 text-green-300 text-xs rounded-lg p-4 overflow-x-auto">PUSH_DRIVER=fcm
FCM_SERVER_KEY=AAAAxxxxxx:APA91bHxxx...</pre>
            </div>
            <div>
                <h3 class="text-sm font-medium text-emerald-700 mb-2">WhatsApp via Meta Cloud API (alternative)</h3>
                <pre class="bg-gray-900 text-green-300 text-xs rounded-lg p-4 overflow-x-auto">WHATSAPP_DRIVER=meta
META_WHATSAPP_TOKEN=EAAxxxxxxx
META_WHATSAPP_PHONE_ID=123456789012345</pre>
            </div>
        </div>
    </div>

    {{-- Log drivers note --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
        <p class="text-sm text-blue-700">
            <strong>Development tip:</strong> Set <code class="bg-blue-100 px-1 rounded">SMS_DRIVER=log</code>,
            <code class="bg-blue-100 px-1 rounded">PUSH_DRIVER=log</code>,
            <code class="bg-blue-100 px-1 rounded">WHATSAPP_DRIVER=log</code> to write all notifications
            to <code class="bg-blue-100 px-1 rounded">storage/logs/laravel.log</code> without sending real messages.
        </p>
    </div>
</div>
@endsection
