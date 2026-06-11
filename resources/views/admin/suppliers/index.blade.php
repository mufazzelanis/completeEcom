@extends('layouts.admin')
@section('title', 'Suppliers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage product suppliers</p>
    <a href="{{ route('admin.suppliers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Add Supplier</span>
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, company, phone…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request('search'))<a href="{{ route('admin.suppliers.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Supplier</th>
                <th class="px-6 py-3 text-left">Contact</th>
                <th class="px-6 py-3 text-left">Location</th>
                <th class="px-6 py-3 text-center">Purchases</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($suppliers as $supplier)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-gray-900">{{ $supplier->name }}</p>
                    @if($supplier->company)<p class="text-xs text-gray-400">{{ $supplier->company }}</p>@endif
                </td>
                <td class="px-6 py-4">
                    @if($supplier->phone)<p class="text-sm text-gray-700">{{ $supplier->phone }}</p>@endif
                    @if($supplier->email)<p class="text-xs text-gray-400">{{ $supplier->email }}</p>@endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $supplier->city }}{{ $supplier->city && $supplier->country ? ', ' : '' }}{{ $supplier->country }}</td>
                <td class="px-6 py-4 text-center">
                    <a href="{{ route('admin.purchases.index', ['supplier' => $supplier->id]) }}" class="text-indigo-600 hover:underline text-sm">{{ $supplier->purchases_count }}</a>
                </td>
                <td class="px-6 py-4 text-center">
                    @if($supplier->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form action="{{ route('admin.suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Delete this supplier?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No suppliers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($suppliers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $suppliers->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
