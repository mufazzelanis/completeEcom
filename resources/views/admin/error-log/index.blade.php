@extends('layouts.admin')
@section('title', 'Error Log')

@section('content')
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $at = fn ($s) => \Illuminate\Support\Carbon::parse($s, config('app.timezone'))->setTimezone($tz);
    $lvl = ['emergency' => '#7f1d1d', 'alert' => '#991b1b', 'critical' => '#b91c1c', 'error' => '#dc2626', 'warning' => '#d97706'];
@endphp
<div class="max-w-6xl">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Error Log</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">The server's recent errors, grouped — the same problem counted once with how often it happened.</p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.error-log.index', $showWarnings ? [] : ['warnings' => 1]) }}"
               class="px-3 py-1.5 rounded-full font-medium {{ $showWarnings ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700' }}">{{ $showWarnings ? '✓ Including warnings' : 'Include warnings' }}</a>
            <a href="{{ route('admin.error-log.index', request()->query()) }}" class="px-3 py-1.5 rounded-full font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">↻ Refresh</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Last 24 hours</p>
            <p class="text-2xl font-extrabold mt-1 {{ $last24h ? 'text-red-600' : 'text-green-600' }}">{{ $last24h ? number_format($last24h) . ' errors' : 'No errors ✓' }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Different problems</p>
            <p class="text-2xl font-extrabold mt-1 text-gray-800 dark:text-gray-100">{{ $groups->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Log files read</p>
            <p class="text-sm mt-1 text-gray-600 dark:text-gray-300">
                @forelse($files as $f){{ $f['name'] }} <span class="text-gray-400">({{ number_format($f['size'] / 1024) }} KB · {{ $f['modified']->setTimezone($tz)->diffForHumans() }})</span><br>@empty — no log files —@endforelse
            </p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($groups as $g)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-4" style="border-left: 4px solid {{ $lvl[$g['level']] ?? '#6b7280' }}">
                <div class="flex flex-wrap items-center gap-2 text-xs mb-1.5">
                    <span class="px-2 py-0.5 rounded-full font-bold uppercase text-white" style="background: {{ $lvl[$g['level']] ?? '#6b7280' }}">{{ $g['level'] }}</span>
                    <span class="px-2 py-0.5 rounded-full font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">{{ number_format($g['count']) }}×</span>
                    <span class="text-gray-500 dark:text-gray-400">last {{ $at($g['last'])->format('d M, h:i:s A') }} ({{ $at($g['last'])->diffForHumans() }})</span>
                    @if($g['count'] > 1)<span class="text-gray-400">· first {{ $at($g['first'])->format('d M, h:i A') }}</span>@endif
                </div>
                <p class="text-sm font-mono text-gray-800 dark:text-gray-100 break-words">{{ $g['message'] }}</p>
                @if($g['where'])<p class="text-xs font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $g['where'] }}</p>@endif
                @foreach($g['urls'] as $u)<p class="text-xs text-gray-400 mt-0.5 truncate">{{ $u }}</p>@endforeach
            </div>
        @empty
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-10 text-center">
                <div class="text-4xl mb-2">✅</div>
                <p class="font-semibold text-gray-800 dark:text-gray-100">No errors in the recent log</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
