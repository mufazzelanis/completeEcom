@extends('layouts.app')
@php
    $pageSeoImage = $page->og_image ?: ($page->image ? Storage::url($page->image) : null);
    // Meta text that only repeats the page title ("Privacy Policy") is ignored — see meaningful_seo().
    $pageSeoTitle = meaningful_seo($page->meta_title, [$page->title]) ?? $page->title . ' | ' . setting('site_name', 'ShopVista');
    $pageSeoDesc  = meaningful_seo($page->meta_description, [$page->title], 50)
        ?? meaningful_seo($page->excerpt, [$page->title], 50)
        ?? Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $page->content))), 155, '…');
@endphp
@section('title', $pageSeoTitle)
@section('meta_description', $pageSeoDesc)
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
    <div class="prose prose-gray dark:prose-invert max-w-none text-gray-700 leading-relaxed">
        {!! demote_h1($page->content) !!}
    </div>
</div>
@endsection
