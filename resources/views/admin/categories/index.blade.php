@extends('layouts.admin')
@section('title', 'Categories')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage product categories and subcategories</p>
    <a href="{{ route('admin.categories.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Category</span>
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-left">Parent</th>
                <th class="px-6 py-3 text-center">Subcategories</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($categories as $category)
                <tr class="hover:bg-gray-50 transition {{ $category->parent_id ? 'bg-gray-50/50' : '' }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            @if($category->parent_id)
                                <span class="text-gray-300 text-lg leading-none pl-4">└</span>
                            @endif
                            @if($category->image)
                                <img src="{{ Storage::url($category->image) }}" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                            @else
                                <div class="w-9 h-9 {{ $category->parent_id ? 'bg-purple-100' : 'bg-indigo-100' }} rounded-lg flex items-center justify-center flex-shrink-0">
                                    <span class="{{ $category->parent_id ? 'text-purple-600' : 'text-indigo-600' }} font-bold text-sm">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-gray-800 text-sm">{{ $category->name }}</p>
                                <p class="text-xs text-gray-400">{{ $category->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($category->parent)
                            <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full text-xs">{{ $category->parent->name }}</span>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold text-gray-700 text-sm">{{ $category->children_count }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="font-semibold text-gray-800">{{ $category->products_count }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <a href="{{ route('admin.categories.edit', $category->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                onsubmit="return confirm('Delete \'{{ $category->name }}\'? Products in this category may be affected.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        No categories found. <a href="{{ route('admin.categories.create') }}" class="text-indigo-600">Create one</a>.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $categories->links() }}</div>
</div>
@endsection
