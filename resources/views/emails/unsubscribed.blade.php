@extends('layouts.app')
@section('title', 'Unsubscribed')

@section('content')
<div class="max-w-lg mx-auto px-4 py-20 text-center">
    <div class="bg-white rounded-2xl shadow-sm p-10">
        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">You've been unsubscribed</h1>
        <p class="text-gray-500 text-sm">You won't receive promotional emails from us anymore. You'll still get order, return, and support updates. You can turn promotional emails back on anytime from your account preferences.</p>
        <a href="{{ route('home') }}" class="inline-block mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Back to Home</a>
    </div>
</div>
@endsection
