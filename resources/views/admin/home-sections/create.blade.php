@extends('layouts.admin')
@section('title', 'Add Homepage Section')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.home-sections.index') }}" class="text-orange-600 hover:text-orange-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Homepage Sections</span>
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-8">
        <form action="{{ route('admin.home-sections.store') }}" method="POST">
            @csrf
            @include('admin.home-sections._form', ['section' => null])
            <div class="flex justify-end space-x-3 pt-6">
                <a href="{{ route('admin.home-sections.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition">Add Section</button>
            </div>
        </form>
    </div>
</div>
@endsection
