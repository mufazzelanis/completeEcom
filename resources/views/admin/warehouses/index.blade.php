@extends('layouts.admin')
@section('title', 'Warehouses')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Warehouses</h1>
        <p class="text-sm text-gray-500 mt-1">Manage storage locations for multi-warehouse inventory</p>
    </div>
    <a href="{{ route('admin.warehouses.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Warehouse
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Warehouse</th>
                <th class="px-6 py-3 text-left">Code</th>
                <th class="px-6 py-3 text-left">Location</th>
                <th class="px-6 py-3 text-left">Manager</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($warehouses as $wh)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $wh->name }}</p>
                            @if($wh->phone)<p class="text-xs text-gray-400">{{ $wh->phone }}</p>@endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4"><span class="font-mono text-sm font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-lg">{{ $wh->code }}</span></td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $wh->city }}@if($wh->city && $wh->address), @endif{{ Str::limit($wh->address, 40) }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $wh->manager_name ?? '—' }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="text-sm font-semibold text-gray-700">{{ $wh->stock_entries_count }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $wh->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $wh->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.warehouse-stock.index', ['warehouse_id' => $wh->id]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Stock</a>
                        <a href="{{ route('admin.warehouses.edit', $wh) }}" class="text-gray-600 hover:text-gray-800 text-sm">Edit</a>
                        <form action="{{ route('admin.warehouses.destroy', $wh) }}" method="POST" onsubmit="return confirm('Delete this warehouse?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-16 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <p>No warehouses yet. <a href="{{ route('admin.warehouses.create') }}" class="text-indigo-600">Add your first warehouse</a></p>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($warehouses->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $warehouses->links() }}</div>@endif
</div>
@endsection
