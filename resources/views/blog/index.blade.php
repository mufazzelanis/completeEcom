@extends('layouts.app')
@section('title', 'Blog')
@section('meta_description', setting('blog_meta_description', 'News, guides, and updates from ' . setting('site_name', 'ShopVista') . '.'))

@push('meta')
{{-- Blog listing structured data — helps Google understand this is a genuine content
     section (not a thin/duplicate page), which matters for both search presentation and
     any ad-network review of the site. --}}
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => setting('site_name', 'ShopVista') . ' Blog',
    'url' => route('blog.index'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
{{-- mx-3/rounded card treatment on mobile, matching the rest of the site's app-like feed
     (see home.blade.php) instead of a bare grid stretched edge to edge. --}}
<div class="max-w-7xl mx-auto px-3 sm:px-4 py-6 sm:py-10">

    {{-- Header: heading + search stack on mobile, sit side by side from sm: up --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Blog</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ setting('blog_tagline', 'News, guides, and updates') }}</p>
        </div>
        <form action="{{ route('blog.index') }}" method="GET" class="flex gap-2">
            <div class="relative flex-1 sm:flex-none">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts…"
                    class="w-full sm:w-64 border border-gray-200 dark:border-gray-700 rounded-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            </div>
            @if(request('tag'))
                <input type="hidden" name="tag" value="{{ request('tag') }}">
            @endif
        </form>
    </div>

    @if(request('search') || request('tag'))
    <div class="mb-6 flex flex-wrap items-center gap-2 text-sm text-gray-500">
        @if(request('search'))<span>Results for "<strong class="text-gray-700 dark:text-gray-300">{{ request('search') }}</strong>"</span>@endif
        @if(request('tag'))<span class="bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 px-2.5 py-1 rounded-full text-xs font-medium">#{{ request('tag') }}</span>@endif
        <a href="{{ route('blog.index') }}" class="text-gray-400 hover:text-gray-600 hover:underline text-xs">Clear filters</a>
    </div>
    @endif

    {{-- Featured posts — a horizontally-scrollable highlight strip on mobile, a proper grid
         from md: up. The controller already fetches these; nothing in the old view ever
         rendered them, so "Featured" toggled on a post had no visible effect anywhere. --}}
    @if(!request('search') && !request('tag') && $featured->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">Featured</h2>
        <div class="flex md:grid md:grid-cols-3 gap-4 overflow-x-auto snap-x snap-mandatory scrollbar-hide -mx-3 px-3 md:mx-0 md:px-0">
            @foreach($featured as $post)
            <a href="{{ route('blog.show', $post) }}" class="relative shrink-0 w-[80%] sm:w-[60%] md:w-auto snap-center rounded-2xl overflow-hidden shadow-sm group aspect-[16/10] bg-gray-100 dark:bg-gray-800">
                @if($post->image)
                    <img src="{{ Storage::url($post->image) }}" alt="{{ $post->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-4">
                    @if($post->category)<span class="inline-block text-[11px] font-semibold text-white/90 bg-white/20 backdrop-blur px-2 py-0.5 rounded-full mb-1.5">{{ $post->category->name }}</span>@endif
                    <p class="text-white font-bold leading-snug line-clamp-2">{{ $post->title }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
        {{-- Posts --}}
        <div class="lg:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                @forelse($posts as $post)
                <article class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition group border border-transparent dark:border-gray-800">
                    @if($post->image)
                    <a href="{{ route('blog.show', $post) }}" class="block aspect-[16/10] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <img src="{{ Storage::url($post->image) }}" alt="{{ $post->title }}" loading="lazy" decoding="async" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </a>
                    @endif
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            @if($post->category)
                            <a href="{{ route('blog.category', $post->category) }}" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">{{ $post->category->name }}</a>
                            @endif
                            @if($post->is_featured)<span class="text-xs bg-yellow-50 dark:bg-yellow-500/15 text-yellow-700 dark:text-yellow-400 px-2 py-0.5 rounded-full">Featured</span>@endif
                        </div>
                        <h2 class="font-bold text-gray-800 mb-2 leading-snug">
                            <a href="{{ route('blog.show', $post) }}" class="hover:text-indigo-600 transition">{{ $post->title }}</a>
                        </h2>
                        @if($post->excerpt)<p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $post->excerpt }}</p>@endif
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $post->published_at?->format('d M Y') }}</span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ number_format($post->views) }}
                            </span>
                        </div>
                    </div>
                </article>
                {{-- One in-feed ad, roughly mid-page — after the 4th post, spanning both grid
                     columns like a banner rather than fighting a post card for its own cell. --}}
                @if($loop->iteration === 4)
                <div class="sm:col-span-2">
                    @include('partials.adsense-unit', ['slot' => setting('adsense_slot_infeed')])
                </div>
                @endif
                {{-- A second, separate in-feed spot further down the page — kept apart from
                     the AdSense one above so enabling both doesn't stack two ads back to
                     back into one "ad wall" in the middle of the feed. --}}
                @if($loop->iteration === 8)
                <div class="sm:col-span-2">
                    @include('partials.adsterra-unit', ['code' => setting('adsterra_code_infeed')])
                </div>
                @endif
                @empty
                <div class="sm:col-span-2 py-16 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-lg mb-2">No posts found.</p>
                    @if(request('search') || request('tag'))<a href="{{ route('blog.index') }}" class="text-indigo-600 hover:underline text-sm">View all posts</a>@endif
                </div>
                @endforelse
            </div>

            @if($posts->hasPages())
            <div class="mt-8">{{ $posts->withQueryString()->links() }}</div>
            @endif
        </div>

        {{-- Sidebar — stacks below the posts on mobile (grid-cols-1), sits alongside from lg: --}}
        <aside class="space-y-5">
            @if($categories->isNotEmpty())
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Categories</h3>
                <ul class="space-y-1">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat) }}" class="flex items-center justify-between text-sm text-gray-600 hover:text-indigo-600 transition py-1.5">
                            <span>{{ $cat->name }}</span>
                            <span class="text-xs text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-full px-2 py-0.5">{{ $cat->posts_count }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(isset($tags) && $tags->count())
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                    <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                        class="px-3 py-1 bg-gray-50 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/15 hover:text-indigo-700 dark:hover:text-indigo-400 text-gray-600 text-xs rounded-full transition {{ request('tag') === $tag->slug ? 'bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-400' : '' }}">
                        #{{ $tag->name }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection
