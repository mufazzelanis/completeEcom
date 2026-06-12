@extends('layouts.admin')
@section('title', 'Email Campaigns')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Email Campaigns</h1>
        <p class="text-sm text-gray-500 mt-0.5">Compose and send email newsletters to your customers</p>
    </div>
    <a href="{{ route('admin.email-campaigns.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Campaign
    </a>
</div>

<div class="grid grid-cols-5 gap-4 mb-6">
    @foreach([
        ['Total', $stats['total'], 'text-gray-700'],
        ['Sent', $stats['sent'], 'text-green-600'],
        ['Scheduled', $stats['scheduled'], 'text-purple-600'],
        ['Draft', $stats['draft'], 'text-gray-500'],
        ['Emails Sent', number_format($stats['emails_sent']), 'text-indigo-600'],
    ] as [$label, $value, $color])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Campaign</th>
                <th class="px-6 py-3 text-left">Recipients</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Sent</th>
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($campaigns as $campaign)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="font-medium text-gray-800">{{ $campaign->name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ $campaign->subject }}</p>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <p>{{ $campaign->recipientLabel() }}</p>
                    <p class="text-xs text-gray-400">~{{ number_format($campaign->recipient_count) }} recipients</p>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $campaign->status_badge }}">{{ ucfirst($campaign->status) }}</span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">
                    @if($campaign->status === 'sent')
                    <p class="font-semibold text-green-600">{{ number_format($campaign->sent_count) }}</p>
                    @if($campaign->failed_count > 0)<p class="text-xs text-red-400">{{ $campaign->failed_count }} failed</p>@endif
                    @else —
                    @endif
                </td>
                <td class="px-6 py-4 text-xs text-gray-500">
                    @if($campaign->sent_at)
                    <p>Sent {{ $campaign->sent_at->format('M d, Y') }}</p>
                    @elseif($campaign->scheduled_at)
                    <p>Scheduled {{ $campaign->scheduled_at->format('M d, Y H:i') }}</p>
                    @else
                    <p>{{ $campaign->created_at->format('M d, Y') }}</p>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.email-campaigns.show', $campaign) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                        @if(in_array($campaign->status, ['draft', 'scheduled']))
                        <a href="{{ route('admin.email-campaigns.edit', $campaign) }}" class="text-gray-500 hover:text-gray-700 text-xs font-medium">Edit</a>
                        @endif
                        <form action="{{ route('admin.email-campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Delete this campaign?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400">No campaigns yet. <a href="{{ route('admin.email-campaigns.create') }}" class="text-indigo-600">Create your first campaign</a></td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $campaigns->links() }}</div>
</div>
@endsection
