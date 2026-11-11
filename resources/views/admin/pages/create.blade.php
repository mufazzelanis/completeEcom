@extends('layouts.admin')
@section('title', 'Add Page')

@section('content')
<div class="max-w-6xl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <a href="{{ route('admin.pages.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        @if(session('error'))<p class="rounded-xl bg-red-50 px-4 py-2 text-sm text-red-700">{{ session('error') }}</p>@endif
    </div>
    @include('admin.pages._form')
</div>
@endsection
