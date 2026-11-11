@extends('layouts.admin')
@section('title', 'Edit Topic — AI Blog')

@section('content')
<div class="max-w-3xl">
    @include('admin.ai-blog._nav')
    <div class="rounded-2xl bg-white dark:bg-gray-900 shadow-sm p-6">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Edit topic</h2>
        @if($topic->error)
            <p class="mb-4 rounded-xl bg-red-50 ring-1 ring-red-200 px-4 py-3 text-sm text-red-700">Last attempt: {{ $topic->error }}</p>
        @endif
        @include('admin.ai-blog._topic-form', ['topic' => $topic, 'products' => $products])
    </div>
</div>
@endsection
