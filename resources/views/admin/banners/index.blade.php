@extends('layouts.admin')
@section('title', 'Banners')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $banners->total() }} banners</p>
    <a href="{{ route('admin.banners.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Banner
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Banner</th>
                <th class="px-6 py-3 text-center">Position</th>
                <th class="px-6 py-3 text-center">Schedule</th>
                <th class="px-6 py-3 text-center">Order</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($banners as $banner)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-20 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            @if($banner->image)
                                <img src="{{ Storage::url($banner->image) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $banner->title }}</p>
                            @if($banner->subtitle)<p class="text-xs text-gray-400 truncate max-w-48">{{ $banner->subtitle }}</p>@endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium capitalize">{{ $banner->position }}</span>
                </td>
                <td class="px-6 py-4 text-center text-xs text-gray-500">
                    @if($banner->starts_at || $banner->ends_at)
                        <span>{{ $banner->starts_at?->format('d/m/y') ?? '∞' }} – {{ $banner->ends_at?->format('d/m/y') ?? '∞' }}</span>
                    @else
                        <span class="text-gray-300">Always</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $banner->sort_order }}</td>
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium {{ $banner->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} hover:opacity-80 transition">
                            {{ $banner->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Delete this banner?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No banners yet. <a href="{{ route('admin.banners.create') }}" class="text-indigo-600">Add one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $banners->links() }}</div>
</div>
@endsection
