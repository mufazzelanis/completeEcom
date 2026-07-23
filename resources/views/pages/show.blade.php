@extends('layouts.app')
@php
    $pageSeoImage = $page->og_image ?: ($page->image ? Storage::url($page->image) : null);
@endphp
@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->excerpt)
@if($page->meta_keywords)@section('meta_keywords', $page->meta_keywords)@endif
@section('canonical', $page->canonical_url ?: route('pages.show', $page))
@if($pageSeoImage)@section('og_image', $pageSeoImage)@endif
@if($page->og_title)@section('og_title', $page->og_title)@endif
@if($page->og_description)@section('og_description', $page->og_description)@endif

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
