@extends('layouts.admin')
@section('title', 'Edit Landing Page')

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.landing-pages.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Landing Pages
    </a>

    <form action="{{ route('admin.landing-pages.update', $landingPage) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.landing-pages._form', ['landingPage' => $landingPage])
    </form>
</div>
@endsection
