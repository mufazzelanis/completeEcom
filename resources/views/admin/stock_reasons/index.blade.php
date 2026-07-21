@extends('layouts.admin')
@section('title', 'Stock Reasons')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Reasons</h1>
        <p class="text-sm text-gray-500 mt-1">Manage the reason options shown when adjusting stock</p>
    </div>
    <a href="{{ route('admin.stock-adjustments.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
        View Stock History →
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
@endif

<div class="space-y-6">
    {{-- Add Reason Form --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Add New Reason</h3>
        <form action="{{ route('admin.stock-reasons.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason Label <span class="text-red-500">*</span></label>
                    <input type="text" name="label" value="{{ old('label') }}" required placeholder="e.g. Damaged in warehouse"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('label') border-red-400 @enderror">
                    @error('label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Applies To</label>
                    <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="any" {{ old('type') == 'any' ? 'selected' : '' }}>Any Type</option>
                        <option value="return_in" {{ old('type') == 'return_in' ? 'selected' : '' }}>Customer Return</option>
                        <option value="manual_in" {{ old('type') == 'manual_in' ? 'selected' : '' }}>Manual Stock In</option>
                        <option value="purchase_in" {{ old('type') == 'purchase_in' ? 'selected' : '' }}>Purchase Received</option>
                        <option value="damage_out" {{ old('type') == 'damage_out' ? 'selected' : '' }}>Damage / Loss</option>
                        <option value="manual_out" {{ old('type') == 'manual_out' ? 'selected' : '' }}>Manual Stock Out</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded text-indigo-600">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add Reason</button>
            </div>
        </form>
    </div>

    {{-- Reasons List --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-50">
            @foreach($reasons as $reason)
            <div x-data="{ editing: false }" class="px-6 py-4">
                <div x-show="!editing">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0 flex items-center gap-3">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">{{ $reason->typeLabel() }}</span>
                            <p class="font-medium text-gray-800 text-sm truncate">{{ $reason->label }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs text-gray-400">#{{ $reason->sort_order }}</span>
                            <form action="{{ route('admin.stock-reasons.toggle', $reason) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium {{ $reason->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} hover:opacity-80 transition">
                                    {{ $reason->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                            <button @click="editing = true" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                            <form action="{{ route('admin.stock-reasons.destroy', $reason) }}" method="POST" onsubmit="return confirm('Delete this reason?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div x-show="editing" x-cloak>
                    <form action="{{ route('admin.stock-reasons.update', $reason) }}" method="POST" class="flex flex-wrap gap-3 items-center">
                        @csrf @method('PUT')
                        <input type="text" name="label" value="{{ $reason->label }}" required
                            class="flex-1 min-w-[200px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <select name="type" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="any" {{ $reason->type == 'any' ? 'selected' : '' }}>Any Type</option>
                            <option value="return_in" {{ $reason->type == 'return_in' ? 'selected' : '' }}>Customer Return</option>
                            <option value="manual_in" {{ $reason->type == 'manual_in' ? 'selected' : '' }}>Manual Stock In</option>
                            <option value="purchase_in" {{ $reason->type == 'purchase_in' ? 'selected' : '' }}>Purchase Received</option>
                            <option value="damage_out" {{ $reason->type == 'damage_out' ? 'selected' : '' }}>Damage / Loss</option>
                            <option value="manual_out" {{ $reason->type == 'manual_out' ? 'selected' : '' }}>Manual Stock Out</option>
                        </select>
                        <input type="number" name="sort_order" value="{{ $reason->sort_order }}"
                            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-24">
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="is_active" value="1" {{ $reason->is_active ? 'checked' : '' }} class="rounded text-indigo-600">
                            <span class="text-sm text-gray-600">Active</span>
                        </label>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save</button>
                        <button type="button" @click="editing = false" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                    </form>
                </div>
            </div>
            @endforeach

            @if($reasons->isEmpty())
            <div class="px-6 py-12 text-center text-gray-400">No stock reasons yet. Add one above.</div>
            @endif
        </div>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
@endsection
