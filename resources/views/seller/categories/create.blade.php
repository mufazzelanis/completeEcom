@extends('layouts.seller')
@section('title', 'Propose a Category')
@section('pageTitle', 'Propose a Category')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">Propose a New Category</h1>

<form action="{{ route('seller.categories.store') }}" method="POST">
    @csrf
    <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 max-w-xl">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Category (optional)</label>
            <select name="parent_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">None — this is a top-level category</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Why do you need this category?</label>
            <textarea name="description" rows="3" maxlength="1000"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description') }}</textarea>
        </div>

        <div class="bg-indigo-50 text-indigo-700 text-xs rounded-xl px-4 py-3">
            Admin reviews new categories before they can be used — you'll be able to pick this category once it's approved.
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">Submit for Approval</button>
            <a href="{{ route('seller.products.create') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
        </div>
    </div>
</form>
@endsection
