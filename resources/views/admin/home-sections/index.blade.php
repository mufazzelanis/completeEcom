@extends('layouts.admin')
@section('title', 'Homepage Sections')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Homepage Sections</h1>
        <p class="text-sm text-gray-500 mt-1">Control what shows on your storefront homepage — Featured Products, Top Selling, New Arrivals, and any custom section you add.</p>
    </div>
    <a href="{{ route('admin.home-sections.create') }}" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Section
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-6 py-3 text-left w-20">Order</th>
                <th class="px-6 py-3 text-left">Section</th>
                <th class="px-6 py-3 text-left">Source</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($sections as $section)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-3">
                    <div class="flex flex-col gap-1">
                        <form action="{{ route('admin.home-sections.move-up', $section) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-gray-400 hover:text-orange-600 {{ $loop->first ? 'invisible' : '' }}" title="Move up">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                        </form>
                        <form action="{{ route('admin.home-sections.move-down', $section) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-gray-400 hover:text-orange-600 {{ $loop->last ? 'invisible' : '' }}" title="Move down">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
                <td class="px-6 py-3">
                    <p class="text-sm font-semibold text-gray-800">{{ $section->title }}</p>
                    @if($section->subtitle)<p class="text-xs text-gray-400">{{ $section->subtitle }}</p>@endif
                </td>
                <td class="px-6 py-3 text-sm text-gray-600">
                    {{ ucwords(str_replace('_', ' ', $section->source_type)) }}
                    @if($section->category)
                        <span class="text-xs text-gray-400">→ {{ $section->category->name }}</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $section->product_limit }}</td>
                <td class="px-6 py-3 text-center">
                    <form action="{{ route('admin.home-sections.toggle', $section) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-medium {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $section->is_active ? 'Visible' : 'Hidden' }}
                        </button>
                    </form>
                </td>
                <td class="px-6 py-3 text-right flex items-center justify-end gap-3">
                    <a href="{{ route('admin.home-sections.edit', $section) }}" class="text-orange-600 text-sm hover:text-orange-800">Edit</a>
                    <form action="{{ route('admin.home-sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Remove this homepage section?')">
                        @csrf @method('DELETE')
                        <button class="text-red-500 text-sm hover:text-red-700">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No homepage sections yet — click "Add Section" to create one.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
