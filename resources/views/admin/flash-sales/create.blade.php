@extends('layouts.admin')
@section('title', 'New Flash Sale')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.flash-sales.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-800">New Flash Sale</h1>
        <p class="text-sm text-gray-500">Set it up, then add the deals on the next screen.</p>
    </div>
</div>

<form action="{{ route('admin.flash-sales.store') }}" method="POST">
    @csrf
    @include('admin.flash-sales._form')
</form>
@endsection
