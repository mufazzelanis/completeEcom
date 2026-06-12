@extends('layouts.app')
@section('title', $page->meta_title ?? $page->title)
@if($page->meta_description)@section('meta_description', $page->meta_description)@endif

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    @if($page->image)
    <img src="{{ Storage::url($page->image) }}" class="w-full h-64 object-cover rounded-2xl mb-8">
    @endif
    <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ $page->title }}</h1>
    @if($page->excerpt)<p class="text-lg text-gray-600 mb-8 leading-relaxed">{{ $page->excerpt }}</p>@endif
    <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
        {!! $page->content !!}
    </div>
</div>
@endsection
