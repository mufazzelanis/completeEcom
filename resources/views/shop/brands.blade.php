@extends('layouts.app')
@section('title', 'All Brands')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-orange-500">Home</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Brands</span>
    </div>

    <div class="mb-6">
        <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">All Brands</h1>
        <p class="text-gray-400 text-sm mt-1">Browse every brand and find what you need</p>
    </div>

    @if($brands->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($brands as $brand)
                <a href="{{ route('shop.index') }}?brand={{ $brand->slug }}"
                   class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 flex flex-col items-center text-center gap-3 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-100 to-orange-50 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden group-hover:from-orange-200 group-hover:to-orange-100 transition-all">
                        @if($brand->logo)
                            <img src="{{ Storage::url($brand->logo) }}" alt="{{ $brand->name }}" class="w-full h-full object-contain p-2">
                        @else
                            <span class="text-orange-500 font-extrabold text-xl">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-bold text-gray-900 group-hover:text-orange-600 transition truncate">{{ $brand->name }}</h2>
                        <p class="text-gray-400 text-xs mt-0.5">{{ $brand->products_count }} {{ Str::plural('product', $brand->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-16 text-gray-400">No brands found.</div>
    @endif
</div>
@endsection
