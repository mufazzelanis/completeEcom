@extends('layouts.admin')
@section('title', 'Facebook Conversions API')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Facebook Conversions API</h1>
        <p class="text-sm text-gray-500 mt-0.5">Server-side events sent to Meta alongside the browser Pixel</p>
    </div>
    <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex-shrink-0">← Back to Pixel Settings</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Sent Today</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['sent_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4 {{ $stats['failed_today'] > 0 ? 'ring-1 ring-red-100' : '' }}">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Failed Today</p>
        <p class="text-2xl font-bold {{ $stats['failed_today'] > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $stats['failed_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Sent All-Time</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['sent_total']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Last Event</p>
        <p class="text-sm font-semibold text-gray-800 mt-1">{{ $stats['last_sent_at'] ? \Illuminate\Support\Carbon::parse($stats['last_sent_at'])->diffForHumans() : 'Never' }}</p>
    </div>
</div>

@if(!\App\Services\Facebook\ConversionsApi::isEnabled())
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4 mb-6 text-sm">
    Conversions API isn't fully configured yet (needs a Pixel ID, an access token, and the
    "Enable Conversions API" toggle on) — no events will send until it is.
    <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" class="font-semibold underline">Finish setup →</a>
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <select name="event_name" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Events</option>
            @foreach($eventNames as $name)
            <option value="{{ $name }}" {{ request('event_name') === $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Statuses</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request('event_name') || request('status'))
        <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Time</th>
                <th class="px-5 py-3 text-left">Event</th>
                <th class="px-5 py-3 text-left">Event ID</th>
                <th class="px-5 py-3 text-center">Status</th>
                <th class="px-5 py-3 text-center">HTTP</th>
                <th class="px-5 py-3 text-left">Response</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-500">
                    {{ $log->sent_at->format('M d, H:i:s') }}
                </td>
                <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $log->event_name }}</span>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500 font-mono">{{ \Illuminate\Support\Str::limit($log->event_id, 24) }}</td>
                <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $log->status === 'sent' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ ucfirst($log->status) }}
                    </span>
                </td>
                <td class="px-5 py-3 text-center text-xs text-gray-500 font-mono">{{ $log->http_status ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-gray-500 max-w-xs"><p class="truncate">{{ $log->response ?? '—' }}</p></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-gray-400">No Conversions API events yet.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
