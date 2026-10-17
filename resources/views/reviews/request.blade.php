@extends('layouts.app')
@section('title', 'Rate your order ' . $order->order_number . ' | ' . setting('site_name', 'ShopVista'))
@section('robots', 'noindex, nofollow')

@section('content')
{{-- Post-delivery review page (ReviewRequestController) — reached from the review link in the
     SMS/email or the customer's order page; no login needed, the link itself is the proof of
     purchase. One card per product: tap a star, optionally say something, one submit. --}}
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">How was your order?</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Order {{ $order->order_number }} · delivered{{ $order->delivered_at ? ' ' . $order->delivered_at->format('d M Y') : '' }}
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Your rating helps other customers choose — it takes less than a minute.</p>
    </div>

    @if(session()->has('review_saved'))
        <div class="mb-6 rounded-xl bg-green-50 dark:bg-green-500/15 border border-green-200 dark:border-gray-700 p-4 text-center">
            <p class="font-semibold text-green-800 dark:text-green-300">Thank you for your review! 💚</p>
            <p class="text-sm text-green-700 dark:text-green-400 mt-1">It will appear on the product page after a quick check.</p>
        </div>
    @endif

    @if($items->isEmpty())
        <p class="text-center text-gray-600 dark:text-gray-400">The products from this order are no longer available to review.</p>
    @else
    <form method="POST" action="{{ route('reviews.request.store', $order->review_token) }}" class="space-y-4">
        @csrf
        @foreach($items as $item)
            @php $review = $existing->get($item->product_id); $locked = $review && $review->is_approved; @endphp
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-4"
                 x-data="{ rating: {{ (int) old("reviews.{$item->product_id}.rating", $review->rating ?? 0) }}, hover: 0 }">
                <div class="flex items-center gap-3">
                    <div class="w-16 h-16 flex-shrink-0 rounded-lg bg-gray-50 overflow-hidden">
                        @if($item->product->image)
                            <img src="{{ image_thumb($item->product->image, 200) }}" alt="" width="64" height="64" class="w-full h-full object-contain" loading="lazy">
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 line-clamp-2">{{ $item->product->name }}</p>
                        @if($locked)
                            <p class="text-xs text-green-700 dark:text-green-400 mt-1">✓ Reviewed — thank you!</p>
                        @elseif($review)
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Your review is waiting for approval — you can still change it.</p>
                        @endif
                    </div>
                </div>

                @unless($locked)
                    <div class="mt-3 flex items-center gap-1" role="radiogroup" aria-label="Rating for {{ $item->product->name }}">
                        @for($s = 1; $s <= 5; $s++)
                            <button type="button" @click="rating = {{ $s }}" @mouseenter="hover = {{ $s }}" @mouseleave="hover = 0"
                                    role="radio" :aria-checked="rating === {{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}"
                                    class="w-10 h-10 flex items-center justify-center">
                                <svg class="w-8 h-8 transition" :class="(hover || rating) >= {{ $s }} ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                        @endfor
                        <input type="hidden" name="reviews[{{ $item->product_id }}][rating]" :value="rating || ''">
                    </div>
                    <textarea name="reviews[{{ $item->product_id }}][comment]" rows="2" maxlength="1000" x-show="rating" x-cloak
                              placeholder="What did you like? (optional)" aria-label="Comment about {{ $item->product->name }}"
                              class="mt-2 w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">{{ old("reviews.{$item->product_id}.comment", $review->comment ?? '') }}</textarea>
                @endunless
            </div>
        @endforeach

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-4">
            <label for="review-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name shown with your review</label>
            <input id="review-name" type="text" name="name" maxlength="60" value="{{ old('name', $defaultName) }}"
                   class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>

        @error('reviews')<p class="text-sm text-red-600">Please tap a star rating for at least one product.</p>@enderror

        <button type="submit" class="w-full bg-orange-700 text-white font-semibold py-3 rounded-xl hover:brightness-110 transition">
            Submit review
        </button>
    </form>
    @endif
</div>
@endsection
