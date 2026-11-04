@extends('layouts.app')
@php
    $siteName = setting('site_name', 'ShopVista');
    $status = $sale?->status;
    $maxOff = (int) $items->max('discount_percent');
    $sorts = ['featured' => 'Featured', 'discount' => 'Biggest discount', 'ending' => 'Almost gone', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'];
@endphp
@section('title', $sale ? ($sale->name . ($maxOff ? " – Up to {$maxOff}% Off" : '') . ' | ' . $siteName) : 'Flash Sale | ' . $siteName)
@if($sale && $sale->description)@section('meta_description', $sale->description)@endif
@section('canonical', $sale ? $sale->url : route('flash-sale.show'))
{{-- No sale (an empty placeholder) or one that's over: thin, short-lived content — keep it out of the index --}}
@if(! $sale || $status === 'ended' || $items->isEmpty())@section('robots', 'noindex, follow')@endif

@section('content')
@include('partials.flash-sale-styles')

@if(! $sale)
    <div class="max-w-xl mx-auto px-4 py-20 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center mb-5">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.983 1.907a.75.75 0 00-1.292-.657l-8 9.5A.75.75 0 003.25 12H9v6.093a.75.75 0 001.292.657l8-9.5A.75.75 0 0018 8H12V1.907z" clip-rule="evenodd"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900">No flash sale right now</h1>
        <p class="mt-2 text-gray-500">New deals drop regularly — check back soon. Meanwhile, these products are already on offer.</p>
        <a href="{{ route('shop.index') }}?on_sale=1" class="inline-flex mt-6 items-center gap-1.5 rounded-full bg-gray-900 text-white px-5 py-2.5 text-sm font-bold hover:bg-gray-800 transition">Browse sale items</a>
    </div>
@else
<div style="{{ $sale->style_vars }}" x-data
     @flash-ended="{{ $status === 'upcoming' ? 'setTimeout(() => location.reload(), 1200)' : '$refs.ended.hidden = false' }}">
    {{-- Hero --}}
    <section class="fs-grad fs-band text-white">
        <div class="max-w-[1200px] mx-auto px-4 py-8 md:py-12 grid md:grid-cols-[1fr_auto] gap-6 md:gap-10 items-center">
            <div>
                <nav class="text-xs opacity-80 mb-3"><a href="{{ route('home') }}" class="hover:underline">Home</a> / Flash Sale</nav>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 ring-1 ring-white/30 px-3 py-1 text-[11px] font-bold uppercase tracking-[.16em]">
                    @if($status === 'live')
                        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full rounded-full bg-white/75 animate-ping"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-white/95"></span></span>
                        Live now
                    @elseif($status === 'upcoming')
                        Coming soon
                    @else
                        Ended
                    @endif
                </span>
                <h1 class="mt-3 text-3xl md:text-5xl font-extrabold tracking-tight leading-[1.05]">{{ $sale->name }}</h1>
                @if($sale->banner_text)<p class="mt-2 text-lg md:text-xl font-semibold opacity-95">{{ $sale->banner_text }}</p>@endif
                @if($sale->description)<p class="mt-2 max-w-xl text-sm md:text-base opacity-85">{{ $sale->description }}</p>@endif
                <div class="mt-5 flex flex-wrap gap-2 text-xs font-bold">
                    <span class="rounded-full bg-white/15 ring-1 ring-white/25 px-3 py-1.5">{{ $items->count() }} {{ Str::plural('deal', $items->count()) }}</span>
                    @if($maxOff)<span class="rounded-full bg-white/15 ring-1 ring-white/25 px-3 py-1.5">Up to {{ $maxOff }}% off</span>@endif
                    <span class="rounded-full bg-white/15 ring-1 ring-white/25 px-3 py-1.5">
                        {{ $status === 'upcoming' ? 'Starts ' . $sale->starts_at->timezone(setting('timezone', 'Asia/Dhaka'))->format('M j, g:i A') : 'Ends ' . $sale->ends_at->timezone(setting('timezone', 'Asia/Dhaka'))->format('M j, g:i A') }}
                    </span>
                </div>
            </div>
            @if($status !== 'ended')
                <div class="md:text-center">
                    <p class="text-xs font-bold uppercase tracking-[.2em] opacity-90 mb-2">{{ $status === 'upcoming' ? 'Starts in' : 'Hurry — ends in' }}</p>
                    @include('partials.flash-countdown', ['target' => $status === 'upcoming' ? $sale->starts_at : $sale->ends_at, 'size' => 'lg'])
                </div>
            @endif
        </div>
    </section>

    <div class="max-w-[1200px] mx-auto px-4 py-6">
        <div x-ref="ended" @if($status !== 'ended') hidden @endif class="mb-5 rounded-xl bg-gray-100 text-gray-700 px-4 py-3 text-sm font-medium">
            This flash sale has ended — prices are back to normal. Keep an eye out for the next one!
        </div>
        @if($status === 'upcoming')
            <div class="mb-5 rounded-xl bg-blue-50 dark:bg-blue-500/10 text-blue-800 dark:text-blue-300 px-4 py-3 text-sm font-medium">
                Sneak peek: these prices unlock when the sale starts. This page refreshes itself the moment it goes live.
            </div>
        @endif

        {{-- Sort --}}
        <div class="flex gap-2 overflow-x-auto fs-track pb-1 mb-4">
            @foreach($sorts as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['sort' => $key === 'featured' ? null : $key]) }}"
                   class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition {{ $sort === $key ? 'fs-grad text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
        </div>

        @if($items->isEmpty())
            <p class="py-16 text-center text-gray-500">Deals for this sale are being lined up — check back shortly.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @foreach($items as $fsp)
                    @include('partials.flash-sale-card', ['fsp' => $fsp, 'sale' => $sale, 'wide' => true])
                @endforeach
            </div>
        @endif

        @if($upcoming->isNotEmpty())
            <h2 class="mt-12 mb-4 text-lg font-extrabold text-gray-900">Coming up next</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($upcoming as $next)
                    <a href="{{ $next->url }}" style="{{ $next->style_vars }}" @flash-ended.stop class="fs-card fs-grad fs-band block rounded-2xl text-white p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[.18em] opacity-90">Starts {{ $next->starts_at->timezone(setting('timezone', 'Asia/Dhaka'))->format('D, M j · g:i A') }}</p>
                        <p class="mt-1 text-lg font-extrabold leading-tight">{{ $next->name }}</p>
                        <p class="mt-0.5 text-xs opacity-90">{{ $next->products_count }} {{ Str::plural('deal', $next->products_count) }}</p>
                        <div class="mt-3">@include('partials.flash-countdown', ['target' => $next->starts_at, 'size' => 'sm'])</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif
@endsection
