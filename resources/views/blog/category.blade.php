@extends('layouts.app')
@section('title', $blogCategory->name . ' — Blog')
@section('meta_description', $blogCategory->description ?: (setting('site_name', 'ShopVista') . ' blog posts in ' . $blogCategory->name . '.'))
@section('canonical', route('blog.category', $blogCategory))

@section('content')
<div class="max-w-7xl mx-auto px-3 sm:px-4 py-6 sm:py-10">
    <div class="mb-6">
        <a href="{{ route('blog.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            All Posts
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $blogCategory->name }}</h1>
        @if($blogCategory->description)<p class="text-gray-500 mt-1">{{ $blogCategory->description }}</p>@endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
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
                        <h2 class="font-bold text-gray-800 mb-2 leading-snug">
                            <a href="{{ route('blog.show', $post) }}" class="hover:text-indigo-600 transition">{{ $post->title }}</a>
                        </h2>
                        @if($post->excerpt)<p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $post->excerpt }}</p>@endif
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $post->published_at?->format('d M Y') }}</span>
                            <span>{{ number_format($post->views) }} views</span>
                        </div>
                    </div>
                </article>
                @if($loop->iteration === 4)
                <div class="sm:col-span-2">
                    @include('partials.adsense-unit', ['slot' => setting('adsense_slot_infeed')])
                </div>
                @endif
                @empty
                <div class="sm:col-span-2 py-16 text-center text-gray-400">No posts in this category yet.</div>
                @endforelse
            </div>

            @if($posts->hasPages())
            <div class="mt-8">{{ $posts->links() }}</div>
            @endif
        </div>

        {{-- Same sidebar as the main blog index — a category page shouldn't be a dead end
             with no way to browse anywhere else on the blog. --}}
        <aside class="space-y-5">
            @if($categories->isNotEmpty())
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Categories</h3>
                <ul class="space-y-1">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat) }}"
                           class="flex items-center justify-between text-sm py-1.5 transition {{ $cat->id === $blogCategory->id ? 'text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 hover:text-indigo-600' }}">
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
                        class="px-3 py-1 bg-gray-50 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/15 hover:text-indigo-700 dark:hover:text-indigo-400 text-gray-600 text-xs rounded-full transition">
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
