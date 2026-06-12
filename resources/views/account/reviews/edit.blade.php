@extends('layouts.account')
@section('title', 'Edit Review')

@section('content')
<div class="flex items-center gap-4 mb-5">
    <a href="{{ route('account.reviews.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">Edit Review</h1>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6 max-w-xl">
    @if($review->product)
    <div class="flex items-center gap-3 mb-5 pb-5 border-b border-gray-100">
        @if($review->product->image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($review->product->image) }}" class="w-14 h-14 object-cover rounded-xl">
        @endif
        <div>
            <p class="font-semibold text-gray-800">{{ $review->product->name }}</p>
            <p class="text-xs text-gray-400">Reviewing this product</p>
        </div>
    </div>
    @endif

    <form action="{{ route('account.reviews.update', $review) }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
            <div class="flex items-center gap-1" x-data="{ rating: {{ old('rating', $review->rating) }} }">
                @for($i = 1; $i <= 5; $i++)
                <label class="cursor-pointer">
                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-on:change="rating = {{ $i }}" {{ old('rating', $review->rating) == $i ? 'checked' : '' }}>
                    <svg class="w-8 h-8 transition" :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"
                        x-on:click="rating = {{ $i }}">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </label>
                @endfor
                @error('rating')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
            <textarea name="comment" rows="5" maxlength="1000" placeholder="Share your thoughts about this product..."
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('comment', $review->comment) }}</textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Update Review</button>
            <a href="{{ route('account.reviews.index') }}" class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
