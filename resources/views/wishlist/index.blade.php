@extends('layouts.app')
@section('title', 'My Wishlist')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-8">My Wishlist</h1>

    @if($wishlists->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
            <svg class="w-20 h-20 text-gray-200 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Your wishlist is empty</h3>
            <p class="text-gray-500 text-sm mb-6">Save items you love to your wishlist</p>
            <a href="{{ route('shop.index') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-indigo-700 transition inline-block">Browse Products</a>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($wishlists as $wishlist)
                @include('partials.product-card', ['product' => $wishlist->product])
            @endforeach
        </div>
    @endif
</div>
@endsection
