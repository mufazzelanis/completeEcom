@extends('layouts.admin')
@section('title', 'AI Blog')

@section('content')
@php
    $ready = \App\Services\AiBlog\Settings::ready();
    $enabled = \App\Services\AiBlog\Settings::enabled();
    $pct = $budget > 0 ? min(100, round($spend / $budget * 100)) : 0;
    $usd = fn ($v) => '$' . number_format((float) $v, 2);
    $bdt = fn ($v) => '৳' . number_format((float) $v * 122);
@endphp
<div class="max-w-6xl">
    @include('admin.ai-blog._nav')

    {{-- Setup checklist: only what's still missing --}}
    @if(!$ready || !$enabled || !$cronOk)
    <div class="mb-6 rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5 space-y-3">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Before the autopilot can write</h2>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <span>{{ $ready ? '✅' : '⬜' }}</span>
                <span>API key saved (Claude or ChatGPT) — <a href="{{ route('admin.ai-blog.settings') }}" class="text-indigo-600 font-medium">Settings</a></span>
            </li>
            <li class="flex items-start gap-2">
                <span>{{ $queued ? '✅' : '⬜' }}</span>
                <span>At least one topic in the queue — <a href="{{ route('admin.ai-blog.topics') }}" class="text-indigo-600 font-medium">Topic Planner</a></span>
            </li>
            <li class="flex items-start gap-2">
                <span>{{ $enabled ? '✅' : '⬜' }}</span>
                <span>Autopilot switched on (Settings) — "Write now" works even while it's off.</span>
            </li>
            <li class="flex items-start gap-2">
                <span>{{ $cronOk ? '✅' : '⚠️' }}</span>
                <span>Server scheduler running — {{ $lastTick ? 'last seen ' . \Carbon\Carbon::parse($lastTick)->diffForHumans() : 'not seen yet' }}.
                    @unless($cronOk)<span class="block text-xs text-gray-500 mt-0.5">cPanel → Cron Jobs → every minute: <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></span>@endunless
                </span>
            </li>
        </ul>
    </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-4">
            <p class="text-xs text-gray-500">Autopilot</p>
            <p class="mt-1 text-lg font-bold {{ $enabled && $ready ? 'text-green-600' : 'text-gray-400' }}">{{ $enabled && $ready ? 'On' : 'Off' }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ \App\Services\AiBlog\Settings::postsPerDay() }}/day · {{ \App\Services\AiBlog\Settings::publishMode() === 'auto' ? 'auto-publish' : 'drafts for review' }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-4">
            <p class="text-xs text-gray-500">Topics in queue</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $queued }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $queued && \App\Services\AiBlog\Settings::postsPerDay() ? 'enough for ~' . ceil($queued / \App\Services\AiBlog\Settings::postsPerDay()) . ' day(s)' : 'add topics to keep posting' }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-4">
            <p class="text-xs text-gray-500">AI posts this month</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $postsMonth }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $perPost ? 'avg ' . $usd($perPost) . ' (' . $bdt($perPost) . ') per post' : 'cost per post appears after the first one' }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-4">
            <p class="text-xs text-gray-500">Spent this month</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $usd($spend) }} <span class="text-xs font-normal text-gray-400">≈ {{ $bdt($spend) }}</span></p>
            @if($budget > 0)
            <div class="mt-2 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden"><div class="h-full {{ $pct >= 90 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ $pct }}%"></div></div>
            <p class="text-xs text-gray-400 mt-1">of {{ $usd($budget) }} limit — stops by itself at the limit</p>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Today's schedule --}}
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">Today's posting times</h2>
            @forelse($slots as $slot)
                <div class="flex items-center justify-between gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0 text-sm">
                    <span class="font-mono font-semibold text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::createFromFormat('H:i', $slot['time'])->format('g:i A') }}</span>
                    <span class="min-w-0 flex-1 truncate text-right">
                        @if($slot['topic']?->post)
                            <a href="{{ route('admin.blog.posts.edit', $slot['topic']->post) }}" class="text-indigo-600 hover:underline">{{ $slot['topic']->post->title }}</a>
                            <span class="text-xs text-gray-400">({{ $slot['topic']->post->status }})</span>
                        @elseif($slot['topic'])
                            <span class="{{ $slot['topic']->status === 'writing' ? 'text-amber-600' : 'text-red-600' }}">{{ $slot['topic']->status === 'writing' ? 'Writing…' : 'Failed — ' . Str::limit($slot['topic']->error, 60) }}</span>
                        @else
                            <span class="text-gray-400">{{ $slot['passed'] ? 'Skipped (autopilot off or queue empty)' : 'Waiting' }}</span>
                        @endif
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400">No posting times — set "Posts per day" in Settings.</p>
            @endforelse
        </div>

        {{-- Needs review --}}
        <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-800 dark:text-gray-100">Needs review</h2>
                <a href="{{ route('admin.blog.posts.index', ['status' => 'draft']) }}" class="text-xs text-indigo-600 font-medium">All drafts →</a>
            </div>
            @forelse($toReview as $post)
                <div class="flex items-start justify-between gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <div class="min-w-0">
                        <a href="{{ route('admin.blog.posts.edit', $post) }}" class="text-sm font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 line-clamp-1">{{ $post->title }}</a>
                        <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }} · {{ $post->hasAlt() ? 'বাংলা + English' : \App\Models\BlogPost::LANGS[$post->primaryLang()] }}
                            @if($post->review_notes)<span class="text-amber-600">· {{ count(array_filter(preg_split('/\R/', $post->review_notes))) }} thing(s) to check</span>@endif
                        </p>
                    </div>
                    <a href="{{ route('blog.show', $post) }}" target="_blank" class="shrink-0 text-xs text-gray-500 hover:text-indigo-600">Preview</a>
                </div>
            @empty
                <p class="text-sm text-gray-400">Nothing waiting. New AI drafts appear here.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="mt-6 rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">Recent activity</h2>
        @forelse($recent as $topic)
            <div class="flex items-start justify-between gap-3 py-2.5 border-b border-gray-100 dark:border-gray-800 last:border-0 text-sm">
                <div class="min-w-0">
                    <p class="font-medium text-gray-800 dark:text-gray-100 line-clamp-1">{{ $topic->post?->title ?? $topic->title }}</p>
                    @if($topic->status === 'failed' || ($topic->error && $topic->status !== 'done'))<p class="text-xs text-red-600 line-clamp-2">{{ $topic->error }}</p>@endif
                </div>
                <div class="shrink-0 text-right">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold
                        {{ ['done' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700', 'writing' => 'bg-amber-100 text-amber-700'][$topic->status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($topic->status) }}</span>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $topic->updated_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">No posts written yet.</p>
        @endforelse
    </div>
</div>
@endsection
