@extends('layouts.admin')
@section('title', 'Alerts')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{
        async markAllRead() {
            await fetch('{{ route('admin.alerts.read-all') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            window.location.reload();
        },
    }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Alerts</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">New orders and newsletter signups. Turn on sound and phone alerts from the bell in the top bar.</p>
        </div>
        @if($unread > 0)
            <button type="button" @click="markAllRead()" class="px-4 py-2 rounded-xl text-sm font-semibold bg-orange-600 text-white hover:bg-orange-700 transition">
                Mark all read ({{ $unread }})
            </button>
        @endif
    </div>

    <div class="flex gap-2 mb-4">
        <a href="{{ route('admin.alerts.index') }}"
           class="px-4 py-1.5 rounded-full text-sm font-medium transition {{ $filter === 'all' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300' }}">All</a>
        <a href="{{ route('admin.alerts.index', ['filter' => 'unread']) }}"
           class="px-4 py-1.5 rounded-full text-sm font-medium transition {{ $filter === 'unread' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300' }}">
            Unread @if($unread > 0)<span class="ml-1 text-xs opacity-80">{{ $unread }}</span>@endif
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm divide-y divide-gray-100 dark:divide-gray-800 overflow-hidden">
        @forelse($alerts as $alert)
            <a href="{{ route('admin.alerts.open', $alert) }}"
               class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition {{ $alert->read_at ? '' : 'bg-orange-50/60 dark:bg-orange-900/10' }}">
                <span class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center
                    {{ $alert->type === 'order' ? 'bg-green-100 text-green-600' : ($alert->type === 'subscriber' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500') }}">
                    @if($alert->type === 'order')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    @elseif($alert->type === 'subscriber')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    @endif
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $alert->title }}</span>
                    @if($alert->body)<span class="block text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $alert->body }}</span>@endif
                    <span class="block text-xs text-gray-400 mt-1" title="{{ $alert->created_at->format('d M Y, h:i A') }}">{{ $alert->created_at->diffForHumans() }}</span>
                </span>
                @unless($alert->read_at)
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-500 flex-shrink-0 mt-2" title="Unread"></span>
                @endunless
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $filter === 'unread' ? 'Nothing unread — you\'re all caught up.' : 'No alerts yet.' }}</p>
                <p class="text-xs text-gray-400 mt-1">New orders and newsletter signups will show up here.</p>
            </div>
        @endforelse
    </div>

    @if($alerts->hasPages())
        <div class="mt-6">{{ $alerts->links() }}</div>
    @endif
</div>
@endsection
