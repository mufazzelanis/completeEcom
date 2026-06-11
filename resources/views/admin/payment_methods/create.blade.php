@extends('layouts.admin')
@section('title', 'Add Payment Method')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.payment-methods.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Payment Methods</span>
    </a>
    <div class="bg-white rounded-2xl shadow-sm p-8">
        @if($errors->any())
        <div class="mb-5 bg-red-50 border border-red-200 rounded-xl p-4"><ul class="text-sm text-red-600 space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('admin.payment-methods.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin.payment_methods._form', ['method' => null])
            <div class="flex justify-end space-x-3 pt-6">
                <a href="{{ route('admin.payment-methods.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Create Method</button>
            </div>
        </form>
    </div>
</div>
@endsection
