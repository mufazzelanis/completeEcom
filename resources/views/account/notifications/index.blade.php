@extends('layouts.account')
@section('title', 'Notifications')
@section('pageTitle', 'Notifications')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Notifications</h1>
    @if($notifications->total() > 0)
    <form action="{{ route('account.notifications.read-all') }}" method="POST">
        @csrf @method('PATCH')
        <button class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">Mark all as read</button>
    </form>
    @endif
</div>

{{-- This page had no dark: variants at all — harmless on its own, except the site-wide
     dark-mode retrofit (resources/css/app.css) already recolors bare `text-gray-800` to a
     light gray for dark mode generally, on the assumption a page's own background goes dark
     too. Without that other half here, the card stayed solid white while its text went
     light-on-white — readable in light mode, closer to invisible in dark mode, which is
     exactly what got reported. Every text/background pairing below now carries its own
     dark: variant explicitly instead of relying on that retrofit alone. --}}
@if($notifications->isEmpty())
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm dark:shadow-black/20 p-16 text-center">
    <svg class="w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    <p class="text-gray-500 dark:text-gray-400 text-sm">You're all caught up!</p>
</div>
@else
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm dark:shadow-black/20 overflow-hidden">
    @foreach($notifications as $notif)
    <div class="flex items-start gap-4 px-5 py-4 border-b border-gray-50 dark:border-gray-700/60 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition {{ !$notif->is_read ? 'bg-indigo-50/30 dark:bg-indigo-500/10' : '' }}">
        <div class="mt-0.5 flex-shrink-0">{!! $notif->icon !!}</div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 flex items-center gap-2">
                        {{ $notif->title }}
                        @if(!$notif->is_read)<span class="w-2 h-2 bg-indigo-500 rounded-full inline-block flex-shrink-0"></span>@endif
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $notif->message }}</p>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap">{{ $notif->created_at->diffForHumans() }}</span>
            </div>
            @if($notif->url)
            <a href="{{ $notif->url }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium mt-1 inline-block">View details →</a>
            @endif
        </div>
    </div>
    @endforeach
    <div class="px-5 py-4">{{ $notifications->links() }}</div>
</div>
@endif
@endsection
