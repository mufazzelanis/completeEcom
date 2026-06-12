@extends('layouts.admin')
@section('title', 'Blog Posts')

@section('content')
<div class="mb-5 bg-white rounded-2xl shadow-sm p-4">
    <form action="{{ route('admin.blog.posts.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..."
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
            <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>Draft</option>
            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
        </select>
        <select name="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700 transition">Filter</button>
        @if(request()->hasAny(['search','status','category']))
            <a href="{{ route('admin.blog.posts.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
        <div class="ml-auto">
            <a href="{{ route('admin.blog.posts.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Post
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Post</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-left">Author</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Views</th>
                <th class="px-6 py-3 text-center">Published</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($posts as $post)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            @if($post->image)
                                <img src="{{ Storage::url($post->image) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate max-w-52">{{ $post->title }}</p>
                            @if($post->is_featured)<span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span>@endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $post->category?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $post->author?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-center">
                    @php $badge = match($post->status) { 'published' => 'bg-green-100 text-green-700', 'scheduled' => 'bg-blue-100 text-blue-700', default => 'bg-gray-100 text-gray-600' }; @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($post->status) }}</span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ number_format($post->views) }}</td>
                <td class="px-6 py-4 text-center text-xs text-gray-500">{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.blog.posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.blog.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this post?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No posts yet. <a href="{{ route('admin.blog.posts.create') }}" class="text-indigo-600">Write one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $posts->links() }}</div>
</div>
@endsection
