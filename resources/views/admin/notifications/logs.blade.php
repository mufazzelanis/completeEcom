@extends('layouts.admin')
@section('title', 'Notification Logs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Notification Logs</h1>
        <a href="{{ route('admin.notifications.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to Overview</a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Channel</label>
            <select name="channel" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Channels</option>
                @foreach(['email','sms','push','whatsapp'] as $ch)
                <option value="{{ $ch }}" @selected(request('channel') === $ch)>{{ strtoupper($ch) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                <option value="skipped" @selected(request('status') === 'skipped')>Skipped</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Event</label>
            <select name="event" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Events</option>
                @foreach(array_keys(config('notifications.events', [])) as $ev)
                <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ str_replace('_', ' ', $ev) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Filter</button>
        <a href="{{ route('admin.notifications.logs') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">Reset</a>
    </form>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-left">Event</th>
                        <th class="px-4 py-3 text-left">Channel</th>
                        <th class="px-4 py-3 text-left">Recipient</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Error</th>
                        <th class="px-4 py-3 text-left">Sent At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">{{ $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded">{{ $log->event_type }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php $cc = ['email'=>'blue','sms'=>'green','push'=>'purple','whatsapp'=>'emerald'][$log->channel] ?? 'gray'; @endphp
                            <span class="inline-block bg-{{ $cc }}-100 text-{{ $cc }}-700 text-xs px-2 py-0.5 rounded-full font-medium">{{ strtoupper($log->channel) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $log->recipient }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-0.5 rounded-full
                                {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : ($log->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-red-500 text-xs max-w-xs truncate" title="{{ $log->error }}">{{ $log->error ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->sent_at?->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">No logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-4 py-3 border-t">{{ $logs->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
