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
    <p class="text-sm text-gray-500">These master switches control whether a channel is used at all. Individual notification types can be configured in the <a href="{{ route('admin.notifications.index') }}" class="text-indigo-600 hover:underline">Notifications</a> section.</p>
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
            <input type="checkbox" name="{{ $key }}" value="1" class="rounded text-indigo-600"
                   @checked(setting($key,'1') == '1')>
            <span class="text-sm text-gray-700">{{ $label }}</span>
        </label>
        @endforeach
    </div>
</div>

<div class="bg-indigo-50 border border-indigo-200 rounded-xl p-5">
    <p class="text-sm text-indigo-700">
        <strong>Manage Templates:</strong>
        Customize the content of each notification in the
        <a href="{{ route('admin.notifications.templates') }}" class="underline font-medium">Notification Templates</a> section.
        View the delivery history in
        <a href="{{ route('admin.notifications.logs') }}" class="underline font-medium">Delivery Logs</a>.
    </p>
</div>

<div class="flex justify-end">
    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Notification Settings</button>
</div>
</form>
@endsection
