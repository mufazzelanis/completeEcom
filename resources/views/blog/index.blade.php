@extends('layouts.app')
@section('title', 'Blog')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="grid grid-cols-4 gap-8">
        {{-- Posts --}}
        <div class="col-span-3">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Blog</h1>
                <form action="{{ route('blog.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..."
                        class="border border-gray-200 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
                    @if(request('tag'))
                        <input type="hidden" name="tag" value="{{ request('tag') }}">
                    @endif
                </form>
            </div>

            @if(request('search') || request('tag'))
            <div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
                @if(request('search'))<span>Results for "<strong>{{ request('search') }}</strong>"</span>@endif
                @if(request('tag'))<span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">#{{ request('tag') }}</span>@endif
                <a href="{{ route('blog.index') }}" class="text-gray-400 hover:text-gray-600 hover:underline text-xs">Clear filters</a>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-6">
                @forelse($posts as $post)
                <article class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition group">
                    @if($post->image)
                    <a href="{{ route('blog.show', $post) }}">
                        <img src="{{ Storage::url($post->image) }}" class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
                    </a>
                    @endif
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            @if($post->category)
                            <a href="{{ route('blog.category', $post->category) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ $post->category->name }}</a>
                            @endif
                            @if($post->is_featured)<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">Featured</span>@endif
                        </div>
                        <h2 class="font-bold text-gray-800 mb-2 leading-snug">
                            <a href="{{ route('blog.show', $post) }}" class="hover:text-indigo-600 transition">{{ $post->title }}</a>
                        </h2>
                        @if($post->excerpt)<p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ $post->excerpt }}</p>@endif
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>{{ $post->published_at?->format('d M Y') }}</span>
                            <span>{{ number_format($post->views) }} views</span>
                        </div>
                    </div>
                </article>
                @empty
                <div class="col-span-2 py-16 text-center text-gray-400">
                    <p class="text-lg mb-2">No posts found.</p>
                    @if(request('q') || request('tag'))<a href="{{ route('blog.index') }}" class="text-indigo-600 hover:underline text-sm">View all posts</a>@endif
                </div>
                @endforelse
            </div>

            <div class="mt-8">{{ $posts->withQueryString()->links() }}</div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Categories</h3>
                <ul class="space-y-2">
                    @foreach($categories as $cat)
                    <li>
                        <a href="{{ route('blog.category', $cat) }}" class="flex items-center justify-between text-sm text-gray-600 hover:text-indigo-600 transition">
                            <span>{{ $cat->name }}</span>
                            <span class="text-xs text-gray-400">{{ $cat->posts_count }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            @if(isset($tags) && $tags->count())
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                    <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}"
                        class="px-3 py-1 bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 text-gray-600 text-xs rounded-full transition {{ request('tag') === $tag->slug ? 'bg-indigo-100 text-indigo-700' : '' }}">
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
