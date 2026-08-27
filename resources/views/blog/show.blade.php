@extends('layouts.app')
@php
    $seoTitle = $blogPost->meta_title ?: $blogPost->title;
    $seoDesc  = $blogPost->meta_description ?: $blogPost->excerpt;
    $seoImage = $blogPost->image ? Storage::url($blogPost->image) : null;
    $canonicalUrl = route('blog.show', $blogPost);
    // ~200 words/minute, floored at 1 so a very short post still reads as "1 min read"
    // rather than "0 min read" — a small thing, but "0 min" reads as broken, not fast.
    $readingMinutes = max(1, (int) ceil(str_word_count(strip_tags($blogPost->content)) / 200));
@endphp
@section('title', $seoTitle)
@section('meta_description', $seoDesc)
@section('meta_keywords', $blogPost->meta_keywords ?? '')
@section('canonical', $canonicalUrl)
@section('og_type', 'article')
@section('og_image', $seoImage ?? '')

@push('meta')
{{-- Article-specific Open Graph properties the layout's generic tags don't cover --}}
<meta property="article:published_time" content="{{ $blogPost->published_at?->toAtomString() }}">
<meta property="article:modified_time" content="{{ $blogPost->updated_at->toAtomString() }}">
@if($blogPost->author)<meta property="article:author" content="{{ $blogPost->author->name }}">@endif
@if($blogPost->category)<meta property="article:section" content="{{ $blogPost->category->name }}">@endif
@foreach($blogPost->tags as $tag)<meta property="article:tag" content="{{ $tag->name }}">@endforeach

{{-- Article structured data --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $seoTitle,
    'description' => $seoDesc,
    'image' => $seoImage ? [$seoImage] : [],
    'datePublished' => $blogPost->published_at?->toAtomString(),
    'dateModified' => $blogPost->updated_at->toAtomString(),
    'author' => ['@type' => 'Person', 'name' => $blogPost->author->name ?? setting('site_name', 'Admin')],
    'publisher' => ['@type' => 'Organization', 'name' => setting('site_name', 'ShopVista')],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-4 py-6 sm:py-10">
    <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Posts
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        {{-- Article --}}
        <article class="lg:col-span-3">
            @if($blogPost->image)
            <img src="{{ Storage::url($blogPost->image) }}" alt="{{ $blogPost->title }}" class="w-full h-56 sm:h-80 md:h-96 object-cover object-top rounded-2xl mb-6 sm:mb-8">
            @endif

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mb-4 text-sm text-gray-500">
                @if($blogPost->category)
                <a href="{{ route('blog.category', $blogPost->category) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">{{ $blogPost->category->name }}</a>
                <span class="text-gray-300 dark:text-gray-700">·</span>
                @endif
                <span>{{ $blogPost->published_at?->format('d M Y') }}</span>
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span>{{ $readingMinutes }} min read</span>
                @if($blogPost->author)
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span>{{ $blogPost->author->name }}</span>
                @endif
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span>{{ number_format($blogPost->views) }} views</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 leading-tight">{{ $blogPost->title }}</h1>

            @if($blogPost->excerpt)
            <p class="text-base sm:text-lg text-gray-600 mb-8 leading-relaxed border-l-4 border-indigo-500 pl-4">{{ $blogPost->excerpt }}</p>
            @endif

            @if($blogPost->tags->count())
            <div class="flex flex-wrap gap-2 mb-8">
                @foreach($blogPost->tags as $tag)
                <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                    class="px-3 py-1 bg-gray-50 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/15 hover:text-indigo-700 dark:hover:text-indigo-400 text-gray-600 text-xs rounded-full transition">
                    #{{ $tag->name }}
                </a>
                @endforeach
            </div>
            @endif

            @include('partials.adsense-unit', ['slot' => setting('adsense_slot_article')])

            {{-- dark:prose-invert — Tailwind Typography's own scoped color system (its own
                 `:where(p),:where(h2)` etc. selectors) isn't touched by the site-wide dark-mode
                 retrofit in app.css, which only targets plain utility classes. Without this,
                 the article body specifically would stay dark, hard-to-read text regardless of
                 everything else on the page correctly going dark. --}}
            <div class="prose prose-gray dark:prose-invert max-w-none leading-relaxed">
                {!! $blogPost->content !!}
            </div>

            {{-- Share — a small but expected "real blog" affordance, and a mild positive
                 signal for anyone (including an ad-network reviewer) judging whether this
                 reads as a genuine content site. Native Web Share on mobile where supported,
                 falling back to a copy-link button everywhere else. --}}
            <div class="mt-10 pt-6 border-t border-gray-100 dark:border-gray-800 flex items-center gap-3"
                 x-data="{
                    copied: false,
                    share() {
                        if (navigator.share) {
                            navigator.share({ title: {{ Js::from($blogPost->title) }}, url: {{ Js::from($canonicalUrl) }} }).catch(() => {});
                        } else {
                            navigator.clipboard.writeText({{ Js::from($canonicalUrl) }});
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        }
                    },
                 }">
                <span class="text-sm font-medium text-gray-500">Share:</span>
                <button type="button" @click="share()" class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342a3 3 0 000 2.316m0-2.316a3 3 0 110-2.316m0 2.316l6.632 3.316m-6.632-5.632l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 8.632a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/></svg>
                    <span x-text="copied ? 'Link copied!' : 'Share'"></span>
                </button>
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-5">
            @if(isset($related) && $related->count())
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Related Posts</h3>
                <div class="space-y-4">
                    @foreach($related as $r)
                    <a href="{{ route('blog.show', $r) }}" class="flex gap-3 group">
                        @if($r->image)
                        <img src="{{ Storage::url($r->image) }}" alt="{{ $r->title }}" loading="lazy" class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-600 transition line-clamp-2">{{ $r->title }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $r->published_at?->format('d M Y') }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($blogPost->category)
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3">In {{ $blogPost->category->name }}</h3>
                <a href="{{ route('blog.category', $blogPost->category) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 text-sm">
                    View all {{ $blogPost->category->name }} posts →
                </a>
            </div>
            @endif

            @include('partials.adsense-unit', ['slot' => setting('adsense_slot_sidebar')])
        </aside>
    </div>
</div>
@endsection
