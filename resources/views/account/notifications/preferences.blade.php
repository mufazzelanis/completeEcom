@extends('layouts.account')
@section('title', 'Notification Preferences')

@section('content')
<div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900">Notification Preferences</h1>
    <p class="text-sm text-gray-500">Choose how and when you want to receive notifications from us.</p>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
         class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('account.notifications.preferences.update') }}" class="space-y-6">
        @csrf @method('PATCH')

        @php
        $groups = [
            'order'  => ['label' => 'Orders & Shipping', 'desc' => 'Order confirmation, status updates, shipping notifications'],
            'return' => ['label' => 'Returns & Refunds',  'desc' => 'Return request updates, refund status'],
            'ticket' => ['label' => 'Support Tickets',   'desc' => 'Replies to your support tickets'],
            'promo'  => ['label' => 'Promotions & Offers', 'desc' => 'Flash sales, promo codes, new arrivals'],
        ];
        $channels = [
            'email'    => ['label' => 'Email',     'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'blue'],
            'sms'      => ['label' => 'SMS',       'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'green'],
            'push'     => ['label' => 'Push',      'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'color' => 'purple'],
            'whatsapp' => ['label' => 'WhatsApp',  'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'color' => 'emerald'],
        ];
        @endphp

        @foreach($groups as $groupKey => $group)
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-5 py-4 border-b bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-900">{{ $group['label'] }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $group['desc'] }}</p>
            </div>
            <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($channels as $channelKey => $channel)
                @php $field = "{$channelKey}_{$groupKey}"; $checked = (bool) ($prefs->$field ?? false); @endphp
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" value="1"
                           class="w-5 h-5 rounded text-{{ $channel['color'] }}-600 border-gray-300 focus:ring-{{ $channel['color'] }}-500"
                           @checked($checked)>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-{{ $channel['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $channel['icon'] }}"/>
                        </svg>
                        <span class="text-sm text-gray-700">{{ $channel['label'] }}</span>
                    </div>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                Save Preferences
            </button>
        </div>
    </form>
</div>
@endsection
