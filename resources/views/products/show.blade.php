@extends('layouts.app')
@section('title', $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Home</a>
        <span>/</span>
        <a href="{{ route('shop.index') }}" class="hover:text-indigo-600">Shop</a>
        <span>/</span>
        <a href="{{ route('shop.category', $product->category->slug) }}" class="hover:text-indigo-600">{{ $product->category->name }}</a>
        <span>/</span>
        <span class="text-gray-900 font-medium line-clamp-1">{{ $product->name }}</span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
            <!-- Images -->
            <div x-data="{ active: '{{ $product->image ? Storage::url($product->image) : '' }}' }">
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-4">
                    <template x-if="active">
                        <img :src="active" alt="{{ $product->name }}" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!active">
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>
                @if($product->images->isNotEmpty())
                    <div class="flex space-x-3 overflow-x-auto">
                        @if($product->image)
                            <button @click="active = '{{ Storage::url($product->image) }}'"
                                class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-indigo-500">
                                <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">
                            </button>
                        @endif
                        @foreach($product->images as $img)
                            <button @click="active = '{{ Storage::url($img->image) }}'"
                                class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 border-gray-200 hover:border-indigo-400">
                                <img src="{{ Storage::url($img->image) }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-indigo-600 text-sm font-medium">{{ $product->category->name }}</span>
                    @auth
                        <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-2 rounded-full hover:bg-red-50 transition {{ $wishlisted ? 'text-red-500' : 'text-gray-400' }}">
                                <svg class="w-6 h-6" fill="{{ $wishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                        </form>
                    @endauth
                </div>

                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>

                <!-- Rating -->
                <div class="flex items-center space-x-2 mb-4">
                    <div class="flex">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= round($product->average_rating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="text-sm text-gray-500">({{ $product->reviews->count() }} reviews)</span>
                </div>

                <!-- Price -->
                <div class="mb-6">
                    @if($product->sale_price)
                        <div class="flex items-center space-x-3">
                            <span class="text-3xl font-bold text-red-600">৳{{ number_format($product->sale_price) }}</span>
                            <span class="text-xl text-gray-400 line-through">৳{{ number_format($product->price) }}</span>
                            <span class="bg-red-100 text-red-600 text-sm px-2 py-1 rounded-full font-medium">
                                {{ round((1 - $product->sale_price / $product->price) * 100) }}% off
                            </span>
                        </div>
                    @else
                        <span class="text-3xl font-bold text-gray-900">৳{{ number_format($product->price) }}</span>
                    @endif
                </div>

                @if($product->short_description)
                    <p class="text-gray-600 mb-6">{{ $product->short_description }}</p>
                @endif

                <!-- Stock -->
                <div class="mb-6">
                    @if($product->stock > 0)
                        <span class="inline-flex items-center bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                            In Stock ({{ $product->stock }} available)
                        </span>
                    @else
                        <span class="inline-flex items-center bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            Out of Stock
                        </span>
                    @endif
                    @if($product->sku)
                        <span class="ml-3 text-xs text-gray-400">SKU: {{ $product->sku }}</span>
                    @endif
                </div>

                <!-- Add to Cart -->
                @if($product->stock > 0)
                    <form action="{{ route('cart.add') }}" method="POST" class="flex items-center space-x-4 mb-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button" onclick="updateQty(-1)" class="px-4 py-3 text-gray-500 hover:bg-gray-100 text-lg font-bold">-</button>
                            <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ $product->stock }}"
                                class="w-16 text-center py-3 border-0 focus:outline-none text-sm font-semibold">
                            <button type="button" onclick="updateQty(1)" class="px-4 py-3 text-gray-500 hover:bg-gray-100 text-lg font-bold">+</button>
                        </div>
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Add to Cart</span>
                        </button>
                    </form>
                @endif

                <!-- Meta -->
                <div class="border-t border-gray-100 pt-6 space-y-3 text-sm text-gray-500">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        <span>Free shipping on orders over ৳2000</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>7-day return policy</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description + Reviews Tabs -->
        <div class="border-t border-gray-100 p-8" x-data="{ tab: 'description' }">
            <div class="flex space-x-6 border-b border-gray-200 mb-6">
                <button @click="tab = 'description'" :class="tab === 'description' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'" class="pb-3 font-medium text-sm">
                    Description
                </button>
                <button @click="tab = 'reviews'" :class="tab === 'reviews' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'" class="pb-3 font-medium text-sm">
                    Reviews ({{ $product->reviews->count() }})
                </button>
            </div>

            <div x-show="tab === 'description'">
                <div class="prose prose-sm max-w-none text-gray-600">
                    {!! nl2br(e($product->description ?? 'No description available.')) !!}
                </div>
            </div>

            <div x-show="tab === 'reviews'" x-cloak>
                @foreach($product->reviews as $review)
                    <div class="border-b border-gray-100 pb-6 mb-6 last:border-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 font-semibold text-sm">{{ strtoupper(substr($review->user->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">{{ $review->user->name }}</p>
                                    <div class="flex">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2">{{ $review->comment }}</p>
                    </div>
                @endforeach

                @auth
                    <div class="bg-gray-50 rounded-xl p-6 mt-6">
                        <h4 class="font-semibold text-gray-800 mb-4">Write a Review</h4>
                        <form action="{{ route('products.review', $product->slug) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <div class="flex space-x-2" x-data="{ rating: 0 }">
                                    @for($i = 1; $i <= 5; $i++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="{{ $i }}" class="sr-only" @change="rating = {{ $i }}">
                                            <svg class="w-8 h-8" :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20" @click="rating = {{ $i }}">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </label>
                                    @endfor
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                                <textarea name="comment" rows="3" placeholder="Share your experience..."
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                                Submit Review
                            </button>
                        </form>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 text-sm">
                        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a> to write a review.
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if($related->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Related Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($related as $relProduct)
                    @include('partials.product-card', ['product' => $relProduct])
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
function updateQty(delta) {
    const input = document.getElementById('qty');
    const max = parseInt(input.max);
    const newVal = parseInt(input.value) + delta;
    input.value = Math.max(1, Math.min(newVal, max));
}
</script>
@endsection
