@extends('layouts.admin')
@section('title', 'Campaign Details')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.email-campaigns.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
        </a>
        <h1 class="text-xl font-bold text-gray-800">{{ $emailCampaign->name }}</h1>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $emailCampaign->status_badge }}">{{ ucfirst($emailCampaign->status) }}</span>
    </div>

    @if(in_array($emailCampaign->status, ['draft', 'scheduled']))
    <form action="{{ route('admin.email-campaigns.send', $emailCampaign) }}" method="POST"
          onsubmit="return confirm('Send to {{ number_format($emailCampaign->recipient_count) }} recipients now?')">
        @csrf
        <button class="flex items-center gap-2 bg-teal-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-teal-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Send Now ({{ number_format($emailCampaign->recipient_count) }} recipients)
        </button>
    </form>
    @endif
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">
    {{-- Details --}}
    <div class="col-span-1 space-y-4">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3 text-sm">
            <h2 class="font-semibold text-gray-800 mb-2">Campaign Info</h2>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-500">Subject</span><span class="font-medium text-gray-800 text-right max-w-40 truncate" title="{{ $emailCampaign->subject }}">{{ $emailCampaign->subject }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Recipients</span><span class="font-medium text-gray-800">{{ $emailCampaign->recipientLabel() }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Count</span><span class="font-medium text-gray-800">{{ number_format($emailCampaign->recipient_count) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">From</span><span class="font-medium text-gray-800">{{ $emailCampaign->from_name ?? config('mail.from.name') }}</span></div>
                @if($emailCampaign->sent_at)
                <div class="flex justify-between"><span class="text-gray-500">Sent At</span><span class="font-medium text-gray-800">{{ $emailCampaign->sent_at->format('M d, Y H:i') }}</span></div>
                @elseif($emailCampaign->scheduled_at)
                <div class="flex justify-between"><span class="text-gray-500">Scheduled</span><span class="font-medium text-purple-600">{{ $emailCampaign->scheduled_at->format('M d, Y H:i') }}</span></div>
                @endif
            </div>
        </div>

        @if(in_array($emailCampaign->status, ['sent', 'sending']))
        <div class="bg-white rounded-2xl shadow-sm p-6">
            @if($emailCampaign->status === 'sending')
            <p class="text-xs text-blue-600 bg-blue-50 rounded-lg px-3 py-2 mb-3">Still sending in the background — refresh this page to see updated progress. The rest goes out automatically within a few minutes.</p>
            @endif
            <div class="grid grid-cols-2 gap-3 text-center">
                <div class="bg-green-50 rounded-xl p-3">
                    <p class="text-xl font-bold text-green-600">{{ number_format($emailCampaign->sent_count) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Delivered</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3">
                    <p class="text-xl font-bold text-red-500">{{ number_format($emailCampaign->failed_count) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Failed</p>
                </div>
            </div>
        </div>
        @endif

        @if(in_array($emailCampaign->status, ['draft','scheduled']))
        <a href="{{ route('admin.email-campaigns.edit', $emailCampaign) }}" class="flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Campaign
        </a>
        @endif
    </div>

    {{-- Preview --}}
    <div class="col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-4">
            <h2 class="font-semibold text-gray-800 mb-3 text-sm">Email Preview</h2>
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 text-xs text-gray-500 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <strong>{{ $emailCampaign->subject }}</strong>
                    @if($emailCampaign->preheader)<span class="text-gray-400">· {{ $emailCampaign->preheader }}</span>@endif
                </div>
                <iframe srcdoc="{{ htmlspecialchars($emailCampaign->content) }}" class="w-full h-96 bg-white" frameborder="0"></iframe>
            </div>
        </div>

        {{-- Delivery Log --}}
        @if($emailCampaign->logs->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Delivery Log (latest 100)</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Recipient</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-left">Error</th>
                    <th class="px-5 py-3 text-center">Time</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($emailCampaign->logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-2.5">
                            <p class="text-xs font-medium text-gray-800">{{ $log->user?->name ?? $log->email }}</p>
                            <p class="text-xs text-gray-400">{{ $log->email }}</p>
                        </td>
                        <td class="px-5 py-2.5 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ match($log->status) { 'sent' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-600', default => 'bg-gray-100 text-gray-500' } }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-2.5 text-xs text-red-500 max-w-xs truncate">{{ $log->error ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-center text-xs text-gray-400">{{ $log->sent_at?->format('H:i:s') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
