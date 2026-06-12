@extends('layouts.app')
@section('title', $page?->meta_title ?? $page?->title ?? 'FAQ')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900">{{ $page?->title ?? 'Frequently Asked Questions' }}</h1>
        @if($page?->excerpt)<p class="text-gray-500 mt-2">{{ $page->excerpt }}</p>@endif
    </div>

    @if($faqs->isEmpty())
    <p class="text-center text-gray-400">No FAQs available yet.</p>
    @else
    <div class="space-y-6">
        @foreach($faqs as $categoryName => $items)
        @if($categoryName)
        <h2 class="text-lg font-semibold text-gray-700 border-b border-gray-100 pb-2">{{ $categoryName }}</h2>
        @endif
        <div x-data="{ open: null }" class="space-y-2">
            @foreach($items as $i => $faq)
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition">
                    <span class="font-medium text-gray-800 text-sm pr-4">{{ $faq->question }}</span>
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse class="px-6 pb-4">
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $faq->answer }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
