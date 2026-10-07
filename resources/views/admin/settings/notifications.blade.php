@extends('admin.settings.layout')
@section('settings-title', 'Notification Settings')

@section('settings-content')
<form method="POST" action="{{ route('admin.settings.update', 'notifications') }}">
@csrf @method('PATCH')

@php
$channels = [
    'email'    => ['label' => 'Email Notifications',    'color' => 'blue',   'desc' => 'Send notifications via email using the configured SMTP settings'],
    'sms'      => ['label' => 'SMS Notifications',      'color' => 'green',  'desc' => 'Send notifications via SMS using Twilio'],
    'push'     => ['label' => 'Push Notifications',     'color' => 'purple', 'desc' => 'Browser/app push notifications via Firebase FCM'],
    'whatsapp' => ['label' => 'WhatsApp Notifications', 'color' => 'emerald','desc' => 'Send notifications via WhatsApp using Twilio or Meta Cloud API'],
];
@endphp

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Global Channel Toggles</h2>
    <p class="text-sm text-gray-500">These master switches control whether a channel is used at all. Individual notification types can be configured in the <a href="{{ route('admin.notifications.index') }}" class="text-orange-600 hover:underline">Notifications</a> section.</p>
    <div class="space-y-3">
        @foreach($channels as $key => $ch)
        <div class="flex items-start justify-between p-4 rounded-xl border bg-gray-50">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $ch['label'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $ch['desc'] }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                <input type="hidden" name="{{ $key }}_notifications_enabled" value="0">
                <input type="checkbox" name="{{ $key }}_notifications_enabled" value="1" class="sr-only peer"
                       @checked(setting("{$key}_notifications_enabled",'1') == '1')>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition peer-checked:bg-{{ $ch['color'] }}-600"></div>
            </label>
        </div>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Admin Alert Events</h2>
    <p class="text-sm text-gray-500">Configure which admin events trigger email alerts.</p>
    @php
    $events = [
        'notify_new_order'     => 'New Order Placed',
        'notify_low_stock'     => 'Low Stock Alert',
        'notify_fraud_flagged' => 'Fraud Flagged Order',
        'notify_new_ticket'    => 'New Support Ticket',
        'notify_new_return'    => 'New Return Request',
        'notify_new_user'      => 'New User Registration',
    ];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($events as $key => $label)
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="{{ $key }}" value="0">
            <input type="checkbox" name="{{ $key }}" value="1" class="rounded text-orange-600"
                   @checked(setting($key,'1') == '1')>
            <span class="text-sm text-gray-700">{{ $label }}</span>
        </label>
        @endforeach
    </div>
</div>

{{-- Admin-only order alerts to a Telegram chat via a bot — separate from the channel
     toggles/templates above, which govern the customer-facing email/sms/push/whatsapp
     system. No per-event template here on purpose: it's one fixed message per event
     (new order, payment status changed), sent to one fixed chat. --}}
<div class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b">Telegram Bot Notifications</h2>
    <p class="text-sm text-gray-500">
        Sends a message to a Telegram chat of your choosing every time a customer places an
        order, and again whenever that order's payment status changes (e.g. a Cash on
        Delivery order marked Paid once collected) — so you can follow orders from your
        phone without opening the admin panel.
    </p>

    <div class="bg-gray-50 border rounded-xl p-4 text-xs text-gray-600 space-y-1.5">
        <p class="font-semibold text-gray-700">How to set this up:</p>
        <p>1. In Telegram, message <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-orange-600 hover:underline font-medium">@BotFather</a> → <code class="bg-white px-1 rounded border">/newbot</code> (or reuse an existing bot) → copy the token it gives you into <strong>Bot Token</strong> below.</p>
        <p>2. Open a chat with your own bot (search its username, e.g. <code class="bg-white px-1 rounded border">@hittechpro_bot</code>) and send it any message — or add it to a group instead, if you want several people to see order alerts.</p>
        <p>3. Visit <code class="bg-white px-1 rounded border">https://api.telegram.org/bot&lt;YOUR_TOKEN&gt;/getUpdates</code> in a browser, find <code class="bg-white px-1 rounded border">"chat":{"id":...}</code> in the response, and paste that number into <strong>Chat ID</strong> below (a group's ID is negative — include the minus sign).</p>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="telegram_notifications_enabled" value="0">
        <input type="checkbox" name="telegram_notifications_enabled" value="1" class="rounded text-orange-600"
               @checked(setting('telegram_notifications_enabled', '0') == '1')>
        <span class="text-sm text-gray-700">Send order alerts to Telegram</span>
    </label>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bot Token</label>
            <input type="password" name="telegram_bot_token" value="{{ setting('telegram_bot_token', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="123456789:AAH...">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Chat ID</label>
            <input type="text" name="telegram_chat_id" value="{{ setting('telegram_chat_id', '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g. 123456789 or -1001234567890">
        </div>
    </div>
</div>

<div class="bg-orange-50 border border-orange-200 rounded-xl p-5">
    <p class="text-sm text-orange-700">
        <strong>Manage Templates:</strong>
        Customize the content of each notification in the
        <a href="{{ route('admin.notifications.templates') }}" class="underline font-medium">Notification Templates</a> section.
        View the delivery history in
        <a href="{{ route('admin.notifications.logs') }}" class="underline font-medium">Delivery Logs</a>.
    </p>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition">Save Notification Settings</button>
</div>
</form>

{{-- Its own top-level form, not nested inside the one above — same reason as the Test
     Email form on the Email settings page (a <form> nested inside another is invalid
     HTML and breaks the outer one). --}}
<div class="bg-white rounded-xl shadow-sm border p-6 mt-6">
    <h2 class="text-base font-semibold text-gray-900 pb-2 border-b mb-4">Test Telegram</h2>
    <form method="POST" action="{{ route('admin.settings.test-telegram') }}">
        @csrf
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">
            Send Test Message
        </button>
    </form>
    <p class="text-xs text-gray-400 mt-2">Save the Bot Token and Chat ID above first, then test.</p>
</div>
@endsection
