@extends('layouts.app')
@section('title', $category->name . ' — Blog')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-8">
        <a href="{{ route('blog.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            All Posts
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ $category->name }}</h1>
        @if($category->description)<p class="text-gray-500 mt-1">{{ $category->description }}</p>@endif
    </div>

    <div class="grid grid-cols-3 gap-6">
        @forelse($posts as $post)
        <article class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition group">
            @if($post->image)
            <a href="{{ route('blog.show', $post) }}">
                <img src="{{ Storage::url($post->image) }}" class="w-full h-44 object-cover group-hover:scale-105 transition duration-300">
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
        @empty
        <div class="col-span-3 py-16 text-center text-gray-400">No posts in this category yet.</div>
        @endforelse
    </div>

    <div class="mt-8">{{ $posts->links() }}</div>
</div>
@endsection
