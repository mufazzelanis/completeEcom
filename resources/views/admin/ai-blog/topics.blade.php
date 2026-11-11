@extends('layouts.admin')
@section('title', 'Topic Planner — AI Blog')

@section('content')
<div class="max-w-6xl" x-data="{ adding: {{ Js::from($errors->any() || $topics->isEmpty()) }} }">
    @include('admin.ai-blog._nav')

    <div class="grid lg:grid-cols-3 items-start gap-6 mb-6">
        {{-- Add a topic --}}
        <div class="lg:col-span-2 rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5">
            <button type="button" @click="adding = !adding" class="flex w-full items-center justify-between text-left">
                <span>
                    <span class="font-semibold text-gray-800 dark:text-gray-100">＋ Add a topic</span>
                    <span class="block text-xs text-gray-400">Pick the products, add your Mitavin Note — the autopilot writes it at the next posting time.</span>
                </span>
                <svg class="w-4 h-4 text-gray-400 transition" :class="adding && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="adding" x-cloak class="mt-4">
                @include('admin.ai-blog._topic-form')
            </div>
        </div>

        {{-- AI ideas --}}
        <div class="rounded-2xl bg-gradient-to-br from-indigo-50 to-white dark:from-gray-900 dark:to-gray-900 ring-1 ring-indigo-100 dark:ring-gray-800 p-5"
             x-data="{ busy: false }">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">💡 Suggest topics</h2>
            <p class="text-xs text-gray-500 mt-1">AI looks at your best sellers, what people search on Google to reach you, and your existing posts — then proposes new ideas. Costs less than ৳1.</p>
            <form method="POST" action="{{ route('admin.ai-blog.suggest') }}" class="mt-3 space-y-2" @submit="busy = true">
                @csrf
                <input type="text" name="focus" maxlength="300" placeholder="Focus (optional): e.g. winter skincare, baby care"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <div class="flex items-center gap-2">
                    <select name="count" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                        @foreach([6, 12, 20] as $n)<option value="{{ $n }}" @selected($n === 12)>{{ $n }} ideas</option>@endforeach
                    </select>
                    <button type="submit" :disabled="busy" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-60 transition">
                        <span x-show="!busy">Suggest</span><span x-show="busy" x-cloak>Thinking… (≈20s)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Suggested ideas waiting for a decision --}}
    @if(!empty($suggestions))
    <form method="POST" action="{{ route('admin.ai-blog.suggestions.add') }}" class="mb-6 rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-5">
        @csrf
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">Suggested ideas — tick the good ones</h2>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-indigo-700">Add ticked to queue</button>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($suggestions as $i => $idea)
                <div class="py-3 grid sm:grid-cols-12 gap-3 items-start">
                    <label class="sm:col-span-5 flex items-start gap-2">
                        <input type="checkbox" name="ideas[{{ $i }}][pick]" value="1" class="mt-1 rounded text-indigo-600">
                        <span>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $idea['title'] }}</span>
                            <span class="block text-xs text-gray-500">{{ $postTypes[$idea['post_type']] ?? '' }} · 🔍 {{ $idea['keyword'] }}</span>
                            <span class="block text-xs text-gray-400">{{ $idea['why'] }}</span>
                            <span class="block text-xs text-indigo-600 mt-0.5">{{ count($idea['product_ids']) }} product(s): {{ \App\Models\Product::whereIn('id', $idea['product_ids'])->pluck('name')->map(fn ($n) => Str::limit($n, 30))->implode(', ') }}</span>
                        </span>
                    </label>
                    <div class="sm:col-span-7">
                        <textarea name="ideas[{{ $i }}][note]" rows="2" placeholder="Mitavin Note for this one (your tip / what customers ask)…"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <input type="hidden" name="ideas[{{ $i }}][title]" value="{{ $idea['title'] }}">
                    <input type="hidden" name="ideas[{{ $i }}][keyword]" value="{{ $idea['keyword'] }}">
                    <input type="hidden" name="ideas[{{ $i }}][post_type]" value="{{ $idea['post_type'] }}">
                    <input type="hidden" name="ideas[{{ $i }}][product_ids]" value="{{ implode(',', $idea['product_ids']) }}">
                </div>
            @endforeach
        </div>
    </form>
    @endif

    {{-- Queue --}}
    <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm">
        <div class="flex flex-wrap gap-1 p-3 border-b border-gray-100 dark:border-gray-800 text-sm">
            @foreach(['queued' => 'Queue', 'writing' => 'Writing', 'done' => 'Written', 'failed' => 'Failed', 'all' => 'All'] as $key => $label)
                <a href="{{ route('admin.ai-blog.topics', ['status' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg font-medium {{ $status === $key ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    {{ $label }} @if($key !== 'all')<span class="opacity-60">{{ $counts[$key] ?? 0 }}</span>@endif
                </a>
            @endforeach
        </div>

        @forelse($topics as $topic)
            <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ $topic->title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $postTypes[$topic->post_type] ?? $topic->post_type }}
                        @if($topic->keyword) · 🔍 {{ $topic->keyword }} @endif
                        · {{ $topic->lang ? \App\Models\BlogPost::LANGS[$topic->lang] : 'default language' }}
                        @if($topic->not_before) · not before {{ $topic->not_before->clone()->setTimezone(\App\Services\AiBlog\Settings::timezone())->format('d M, g:i A') }} @endif
                    </p>
                    <p class="text-xs text-indigo-600 mt-0.5 line-clamp-1">
                        @if($topic->product_ids)
                            {{ collect($topic->product_ids)->map(fn ($id) => $productNames[$id] ?? null)->filter()->map(fn ($n) => Str::limit($n, 28))->implode(' · ') }}
                        @else
                            Best sellers of {{ collect([$topic->category?->name, $topic->brand?->name])->filter()->implode(' / ') }}
                        @endif
                    </p>
                    @if(!trim((string) $topic->note))<p class="text-xs text-amber-600 mt-0.5">No Mitavin Note — will be kept as a draft.</p>@endif
                    @if($topic->error && $topic->status !== 'done')<p class="text-xs text-red-600 mt-1">{{ $topic->error }}</p>@endif
                    @if($topic->post)
                        <a href="{{ route('admin.blog.posts.edit', $topic->post) }}" class="inline-block mt-1 text-xs font-semibold text-green-700 hover:underline">→ {{ $topic->post->title }} ({{ $topic->post->status }})</a>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($topic->status === 'writing' || $topic->run_now)
                        <span class="text-xs font-semibold text-amber-600">Writing…</span>
                    @elseif($topic->status !== 'done')
                        <form method="POST" action="{{ route('admin.ai-blog.topics.run', $topic) }}">
                            @csrf
                            <button class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3 py-1.5">Write now</button>
                        </form>
                    @endif
                    @if($topic->status !== 'writing')
                        <a href="{{ route('admin.ai-blog.topics.edit', $topic) }}" class="text-xs font-medium text-gray-600 hover:text-indigo-600 border border-gray-200 rounded-lg px-3 py-1.5">Edit</a>
                        <form method="POST" action="{{ route('admin.ai-blog.topics.destroy', $topic) }}" x-data @submit.prevent="if (window.confirm('Remove this topic?')) $el.submit()">
                            @csrf @method('DELETE')
                            <button class="text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg px-2 py-1.5">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-gray-400">No topics here.</p>
        @endforelse
        @if($topics->hasPages())<div class="p-4">{{ $topics->links() }}</div>@endif
    </div>
</div>
@endsection
