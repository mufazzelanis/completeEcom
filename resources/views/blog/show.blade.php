@extends('layouts.app')
@php
    $seoTitle = $blogPost->meta_title ?: $blogPost->title;
    $seoDesc  = $blogPost->meta_description ?: $blogPost->excerpt;
    $seoImage = $blogPost->image ? Storage::url($blogPost->image) : null;
    $canonicalUrl = route('blog.show', $blogPost);
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
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="grid grid-cols-4 gap-8">
        {{-- Article --}}
        <article class="col-span-3">
            @if($blogPost->image)
            <img src="{{ Storage::url($blogPost->image) }}" alt="{{ $blogPost->title }}" class="w-full h-80 md:h-96 object-cover object-top rounded-2xl mb-8">
            @endif

            <div class="flex items-center gap-3 mb-4 text-sm text-gray-500">
                @if($blogPost->category)
                <a href="{{ route('blog.category', $blogPost->category) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $blogPost->category->name }}</a>
                <span>·</span>
                @endif
                <span>{{ $blogPost->published_at?->format('d M Y') }}</span>
                <span>·</span>
                <span>{{ $blogPost->author?->name }}</span>
                <span>·</span>
                <span>{{ number_format($blogPost->views) }} views</span>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4 leading-tight">{{ $blogPost->title }}</h1>

            @if($blogPost->excerpt)
            <p class="text-lg text-gray-600 mb-8 leading-relaxed border-l-4 border-indigo-500 pl-4">{{ $blogPost->excerpt }}</p>
            @endif

            @if($blogPost->tags->count())
            <div class="flex flex-wrap gap-2 mb-8">
                @foreach($blogPost->tags as $tag)
                <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                    class="px-3 py-1 bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 text-gray-600 text-xs rounded-full transition">
                    #{{ $tag->name }}
                </a>
                @endforeach
            </div>
            @endif

            <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
                {!! nl2br(e($blogPost->content)) !!}
            </div>
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-6">
            @if(isset($related) && $related->count())
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Related Posts</h3>
                <div class="space-y-4">
                    @foreach($related as $r)
                    <a href="{{ route('blog.show', $r) }}" class="flex gap-3 group">
                        @if($r->image)
                        <img src="{{ Storage::url($r->image) }}" class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
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
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3">In {{ $blogPost->category->name }}</h3>
                <a href="{{ route('blog.category', $blogPost->category) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    View all {{ $blogPost->category->name }} posts →
                </a>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection
