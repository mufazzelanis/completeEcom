@extends('layouts.app')
@section('title', 'All Categories')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-orange-700">Home</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Categories</span>
    </div>

    <div class="mb-6">
        <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">All Categories</h1>
        <p class="text-gray-400 text-sm mt-1">Browse every category and find what you need</p>
    </div>

    @if($categories->count() > 0)
        {{-- items-start — CSS Grid stretches every item to its row's tallest by default, and
             with some categories having a dozen+ subcategory pills and others having none or
             one, that left the empty ones (e.g. a category with 0 children sitting next to one
             with 12) with a wall of dead whitespace at the bottom, matching its tall neighbor's
             height for no reason — reported as the grid "looking off". Each card now sizes to
             its own content; short ones stay short. --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
            @foreach($categories as $category)
                <div class="group/card relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg dark:hover:shadow-black/30 hover:-translate-y-0.5 hover:border-orange-200 dark:hover:border-orange-500/30 transition-all duration-200 overflow-hidden">
                    {{-- Same brand-gradient accent strip used on the nav's category/brand
                         dropdown panels elsewhere on the site, so this page reads as part of
                         the same design system rather than a plain, separately-styled list. --}}
                    <div class="h-1 bg-gradient-to-r from-orange-500 via-pink-500 to-orange-400"></div>
                    <div class="p-5">
                        <a href="{{ route('shop.category', $category->slug) }}" class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-50 dark:from-orange-500/20 dark:to-orange-500/10 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden group-hover/card:from-orange-200 group-hover/card:to-orange-100 dark:group-hover/card:from-orange-500/30 dark:group-hover/card:to-orange-500/15 group-hover/card:scale-105 transition-all duration-200">
                                @if($category->image)
                                    <img src="{{ image_thumb($category->image, 200) }}" alt="{{ $category->name }}" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                @else
                                    <span class="text-orange-700 dark:text-orange-400 font-extrabold text-xl">{{ strtoupper(substr($category->name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="font-bold text-gray-900 dark:text-gray-100 group-hover/card:text-orange-600 dark:group-hover/card:text-orange-400 transition truncate">{{ $category->name }}</h2>
                                <p class="text-gray-400 dark:text-gray-500 text-xs mt-0.5">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</p>
                            </div>
                            <svg class="w-4 h-4 flex-shrink-0 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 group-hover/card:opacity-100 group-hover/card:translate-x-0 group-hover/card:text-orange-500 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </a>

                        @if($category->children->count() > 0)
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-50 dark:border-gray-700/60">
                                @foreach($category->children as $child)
                                    <a href="{{ route('shop.category', $child->slug) }}"
                                       class="text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 hover:bg-orange-50 dark:hover:bg-orange-500/10 hover:text-orange-600 dark:hover:text-orange-400 px-3 py-1.5 rounded-full transition">
                                        {{ $child->name }} <span class="text-gray-400 dark:text-gray-500">({{ $child->products_count }})</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 text-gray-400">No categories found.</div>
    @endif
</div>
@endsection
