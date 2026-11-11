@extends('layouts.app')
@php
    // $view: this page's language version of the post (BlogPost::inLang) — Bangla and English
    // each have their own URL, title, description and content
    $view ??= $blogPost->inLang(null);
    $preview ??= false;
    $seoTitle = meaningful_seo($view['meta_title'], [$view['title']]) ?? $view['title'];
    $seoDesc  = meaningful_seo($view['meta_description'], [$view['title']], 50)
        ?? meaningful_seo($view['excerpt'], [$view['title']], 50)
        ?? Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($view['content']))), 155, '…');
    $seoImage = $blogPost->image ? Storage::url($blogPost->image) : null;
    $langUrls = $blogPost->languageUrls();
    $canonicalUrl = $langUrls[$view['lang']] ?? route('blog.show', $blogPost);
    // ~200 words/minute, floored at 1 so a very short post still reads as "1 min read"
    // rather than "0 min read" — a small thing, but "0 min" reads as broken, not fast.
    // (words = runs of letters, so Bangla counts too — str_word_count only knows A–Z)
    $readingMinutes = max(1, (int) ceil(count(preg_split('/\s+/u', trim(strip_tags($view['content'])), -1, PREG_SPLIT_NO_EMPTY)) / 200));
    $isBn = $view['lang'] === 'bn';
@endphp
@section('title', $seoTitle)
@section('meta_description', $seoDesc)
@section('meta_keywords', $blogPost->meta_keywords ?? '')
@section('canonical', $canonicalUrl)
@section('html_lang', $view['lang'])
@section('og_type', 'article')
@section('og_image', $seoImage ?? '')
@if($preview)
@section('robots', 'noindex, nofollow')
@endif

@push('meta')
{{-- Bangla ⇄ English versions of this post point at each other, so Google shows each searcher
     the version in their language instead of treating the pair as duplicates --}}
@if(count($langUrls) > 1)
@foreach($langUrls as $code => $url)
<link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ route('blog.show', $blogPost) }}">
@endif
{{-- Article-specific Open Graph properties the layout's generic tags don't cover --}}
<meta property="article:published_time" content="{{ $blogPost->published_at?->toAtomString() }}">
<meta property="article:modified_time" content="{{ $blogPost->updated_at->toAtomString() }}">
@if($blogPost->author)<meta property="article:author" content="{{ $blogPost->author->name }}">@endif
@if($blogPost->category)<meta property="article:section" content="{{ $blogPost->category->name }}">@endif
@foreach($blogPost->tags as $tag)<meta property="article:tag" content="{{ $tag->name }}">@endforeach

{{-- Article structured data --}}
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $seoTitle,
    'description' => $seoDesc,
    'image' => $seoImage ? [$seoImage] : [],
    'inLanguage' => $view['lang'],
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

    @if($preview)
    <div class="mb-5 rounded-xl bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-2">
        <span><strong>Preview</strong> — this post is a {{ $blogPost->status }} and only admins can see it. Search engines are told not to index this page.</span>
        <a href="{{ route('admin.blog.posts.edit', $blogPost) }}" class="font-semibold underline">Edit / publish →</a>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        {{-- Article --}}
        <article class="lg:col-span-3" lang="{{ $view['lang'] }}">
            @if(count($langUrls) > 1)
            {{-- Language switch: each version is its own page (Turbo swaps it in without a full reload;
                 hover prefetch makes it instant). The reader's choice is remembered for the next post. --}}
            <nav aria-label="Language" class="mb-4 inline-flex rounded-full bg-gray-100 dark:bg-gray-800 p-1 text-sm font-semibold" data-blog-lang="{{ $view['lang'] }}">
                @foreach($langUrls as $code => $url)
                    @if($code === $view['lang'])
                        <span aria-current="true" class="px-4 py-1.5 rounded-full bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm">{{ \App\Models\BlogPost::LANGS[$code] }}</span>
                    @else
                        <a href="{{ $url }}" hreflang="{{ $code }}" lang="{{ $code }}" data-blog-lang-pick="{{ $code }}" class="px-4 py-1.5 rounded-full text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">{{ \App\Models\BlogPost::LANGS[$code] }}</a>
                    @endif
                @endforeach
            </nav>
            <script>
                (function () {
                    var nav = document.currentScript.previousElementSibling, here = nav.getAttribute('data-blog-lang');
                    var other = nav.querySelector('[data-blog-lang-pick]');
                    var store = function (v) { try { v ? localStorage.setItem('blogLang', v) : 0; return localStorage.getItem('blogLang'); } catch (e) { return null; } };
                    other.addEventListener('click', function () { store(other.getAttribute('data-blog-lang-pick')); });
                    // a reader who chose the other language before gets that version straight away
                    var pref = store(null);
                    if (pref && pref !== here && pref === other.getAttribute('data-blog-lang-pick')) {
                        window.Turbo ? Turbo.visit(other.href, { action: 'replace' }) : location.replace(other.href);
                    }
                })();
            </script>
            @endif

            @if($blogPost->image)
            {{-- No fixed height / object-cover here on purpose — a forced crop box cuts off
                 whatever the author happened to put near the edges of their image (exactly
                 what was happening before: wide banners with icons/text spread across the
                 whole frame lost their top or bottom the moment the box's aspect ratio
                 didn't match the source). w-full + h-auto always shows the complete image,
                 scaled to the column width, on every screen size. --}}
            <img src="{{ Storage::url($blogPost->image) }}" alt="{{ $view['title'] }}" class="w-full h-auto rounded-2xl mb-6 sm:mb-8">
            @endif

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mb-4 text-sm text-gray-500">
                @if($blogPost->category)
                <a href="{{ route('blog.category', $blogPost->category) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 font-medium">{{ $blogPost->category->name }}</a>
                <span class="text-gray-300 dark:text-gray-700">·</span>
                @endif
                {{-- Publish date deliberately left out of the visible meta line — showing it
                     just makes any post read as "old news" the moment it's not brand new,
                     which works against a blog whose whole feel is meant to be current. --}}
                <span>{{ $isBn ? 'পড়তে ' . $readingMinutes . ' মিনিট' : $readingMinutes . ' min read' }}</span>
                @if($blogPost->author)
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span>{{ $blogPost->author->name }}</span>
                @endif
                <span class="text-gray-300 dark:text-gray-700">·</span>
                <span>{{ number_format($blogPost->views) }} views</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4 leading-tight">{{ $view['title'] }}</h1>

            @if($view['excerpt'])
            <p class="text-base sm:text-lg text-gray-600 mb-8 leading-relaxed border-l-4 border-indigo-500 pl-4">{{ $view['excerpt'] }}</p>
            @endif

            @include('partials.adsense-unit', ['slot' => setting('adsense_slot_article')])

            {{-- dark:prose-invert — Tailwind Typography's own scoped color system (its own
                 `:where(p),:where(h2)` etc. selectors) isn't touched by the site-wide dark-mode
                 retrofit in app.css, which only targets plain utility classes. Without this,
                 the article body specifically would stay dark, hard-to-read text regardless of
                 everything else on the page correctly going dark. --}}
            {{-- $body: the sanitized content with its product blocks already rendered as cards
                 (App\Support\BlogShop::render). data-blog-body: what the reading tracker measures
                 scroll depth against (blog/partials/tracker). --}}
            <div data-blog-body class="prose prose-gray dark:prose-invert max-w-none leading-relaxed">
                {!! $body !!}
            </div>

            {{-- Kept below the article body (not stacked with the AdSense in-article unit
                 above the content) so both, if enabled together, don't sit right on top of
                 each other. --}}
            @include('partials.adsterra-unit', ['code' => setting('adsterra_code_article')])

            {{-- Share — a small but expected "real blog" affordance, and a mild positive
                 signal for anyone (including an ad-network reviewer) judging whether this
                 reads as a genuine content site. Native Web Share on mobile where supported,
                 falling back to a copy-link button everywhere else. --}}
            <div class="mt-10 pt-6 border-t border-gray-100 dark:border-gray-800 flex items-center gap-3"
                 x-data="{
                    copied: false,
                    share() {
                        if (navigator.share) {
                            navigator.share({ title: {{ Js::from($view['title']) }}, url: {{ Js::from($canonicalUrl) }} }).catch(() => {});
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

            {{-- products the article is about: the ones the author picked (BlogShop::forPost), else
                 matched from its links / title (BlogController::productsFor) — links from the post into the shop --}}
            @if(isset($shopProducts) && $shopProducts->isNotEmpty())
            <section id="blog-shop" class="mt-8 rounded-2xl bg-gradient-to-br from-orange-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-900 ring-1 ring-orange-100 dark:ring-gray-800 p-3 sm:p-5" aria-labelledby="blog-shop-h">
                <div class="flex items-end justify-between gap-3 mb-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Ready to try it?</p>
                        <h2 id="blog-shop-h" class="mt-0.5 text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">🛍️ Shop the products in this article</h2>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold text-orange-700 dark:text-orange-400 hover:underline whitespace-nowrap">View all →</a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3">
                    @foreach($shopProducts as $product)
                        @include('blog.partials.product', ['product' => $product, 'variant' => 'tile', 'place' => 'shelf'])
                    @endforeach
                </div>
            </section>
            @endif
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-5">
            @include('partials.adsterra-unit', ['code' => setting('adsterra_code_sidebar')])

            @if(isset($related) && $related->count())
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Related Posts</h3>
                <div class="space-y-4">
                    @foreach($related as $r)
                    <a href="{{ route('blog.show', $r) }}" class="flex gap-3 group">
                        @if($r->image)
                        <img src="{{ image_thumb($r->image, 200) }}" alt="{{ $r->title }}" loading="lazy" class="w-16 h-16 object-cover rounded-xl flex-shrink-0">
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 group-hover:text-indigo-600 transition line-clamp-2">{{ $r->title }}</p>
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

            {{-- desktop: the article's products stay in reach while reading (phones get the sticky bar instead) --}}
            @if(isset($shopProducts) && $shopProducts->isNotEmpty())
            <div class="hidden lg:block lg:sticky lg:top-24 bg-white dark:bg-gray-900 rounded-2xl shadow-sm ring-1 ring-gray-100 dark:ring-gray-800 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">In this article</p>
                <div class="mt-3 space-y-3">
                    @foreach($shopProducts->take(4) as $product)
                        @include('blog.partials.product', ['product' => $product, 'variant' => 'mini', 'place' => 'sidebar'])
                    @endforeach
                </div>
                <a href="#blog-shop" class="mt-4 flex items-center justify-center gap-1 h-9 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-bold hover:opacity-90 transition">
                    Shop {{ $shopProducts->count() > 1 ? 'all ' . $shopProducts->count() . ' products' : 'this product' }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </a>
            </div>
            @endif
        </aside>
    </div>
</div>
@include('partials.page-view-beacon')
@include('blog.partials.tracker')
@endsection
