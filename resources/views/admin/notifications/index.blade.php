@extends('layouts.admin')
@section('title', 'Notifications Overview')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Notification System</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.notifications.templates') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                Manage Templates
            </a>
            <a href="{{ route('admin.notifications.settings') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                Settings
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
        $channelMeta = [
            'email'    => ['label' => 'Email',     'color' => 'blue',   'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            'sms'      => ['label' => 'SMS',       'color' => 'green',  'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
            'push'     => ['label' => 'Push',      'color' => 'purple', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
            'whatsapp' => ['label' => 'WhatsApp',  'color' => 'emerald','icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
            'failed'   => ['label' => 'Failed',    'color' => 'red',    'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            'today'    => ['label' => 'Today',     'color' => 'amber',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ];
        @endphp
        @foreach($channelMeta as $key => $meta)
        <div class="bg-white rounded-xl shadow-sm border p-4 flex flex-col gap-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">{{ $meta['label'] }}</span>
                <svg class="w-5 h-5 text-{{ $meta['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/>
                </svg>
            </div>
            <span class="text-2xl font-bold text-gray-900">{{ number_format($stats[$key]) }}</span>
        </div>
        @endforeach
    </div>

    {{-- Recent Logs --}}
    <div class="bg-white rounded-xl shadow-sm border">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900">Recent Notifications</h2>
            <a href="{{ route('admin.notifications.logs') }}" class="text-sm text-indigo-600 hover:underline">View all logs</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Event</th>
                        <th class="px-4 py-3 text-left">Channel</th>
                        <th class="px-4 py-3 text-left">Recipient</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Sent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentLogs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded">{{ $log->event_type }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                            $channelColors = ['email'=>'blue','sms'=>'green','push'=>'purple','whatsapp'=>'emerald'];
                            $cc = $channelColors[$log->channel] ?? 'gray';
                            @endphp
                            <span class="inline-block bg-{{ $cc }}-100 text-{{ $cc }}-700 text-xs px-2 py-0.5 rounded-full">{{ strtoupper($log->channel) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 truncate max-w-xs">{{ $log->recipient }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full
                                {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->sent_at?->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No notifications sent yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
