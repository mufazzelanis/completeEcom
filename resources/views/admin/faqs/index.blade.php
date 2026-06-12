@extends('layouts.admin')
@section('title', 'FAQs')

@section('content')
<div x-data="faqManager()" class="space-y-6">
    {{-- Add FAQ Form --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Add New FAQ</h3>
        <form action="{{ route('admin.faqs.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question <span class="text-red-500">*</span></label>
                    <input type="text" name="question" value="{{ old('question') }}" required placeholder="What is your question?"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Answer <span class="text-red-500">*</span></label>
                    <textarea name="answer" rows="3" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('answer') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Shipping, Returns, Payment"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        list="faq-categories">
                    <datalist id="faq-categories">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded text-indigo-600">
                            <span class="text-sm font-medium text-gray-700">Active</span>
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add FAQ</button>
        </form>
    </div>

    {{-- FAQ List --}}
    @foreach($faqs->groupBy('category') as $categoryName => $items)
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-700 text-sm">{{ $categoryName ?: 'Uncategorized' }}</h3>
            <span class="text-xs text-gray-400">{{ $items->count() }} questions</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($items as $faq)
            <div x-data="{ editing: false }" class="px-6 py-4">
                <div x-show="!editing">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-800 text-sm">{{ $faq->question }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($faq->answer, 120) }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form action="{{ route('admin.faqs.toggle', $faq) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium {{ $faq->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} hover:opacity-80 transition">
                                    {{ $faq->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                            <button @click="editing = true" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                            <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" onsubmit="return confirm('Delete this FAQ?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div x-show="editing" x-cloak>
                    <form action="{{ route('admin.faqs.update', $faq) }}" method="POST" class="space-y-3">
                        @csrf @method('PUT')
                        <input type="text" name="question" value="{{ $faq->question }}" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <textarea name="answer" rows="3" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ $faq->answer }}</textarea>
                        <div class="flex gap-3">
                            <input type="text" name="category" value="{{ $faq->category }}" placeholder="Category"
                                class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-40">
                            <input type="number" name="sort_order" value="{{ $faq->sort_order }}"
                                class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-28">
                            <label class="flex items-center gap-1.5">
                                <input type="checkbox" name="is_active" value="1" {{ $faq->is_active ? 'checked' : '' }} class="rounded text-indigo-600">
                                <span class="text-sm text-gray-600">Active</span>
                            </label>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save</button>
                            <button type="button" @click="editing = false" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    @if($faqs->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm px-6 py-12 text-center text-gray-400">No FAQs yet. Add one above.</div>
    @endif
</div>

<style>[x-cloak]{display:none!important}</style>
@endsection
