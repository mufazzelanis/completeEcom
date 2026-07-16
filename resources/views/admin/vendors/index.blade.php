@extends('layouts.admin')
@section('title', 'Vendors')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage marketplace sellers ({{ $pendingCount }} pending)</p>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search business name, email…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 max-w-xs">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            @foreach(['pending','approved','rejected','suspended'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request('search') || request('status'))<a href="{{ route('admin.vendors.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Business</th>
                <th class="px-6 py-3 text-left">Owner</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($vendors as $vendor)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-gray-900">{{ $vendor->business_name }}</p>
                    @if($vendor->email)<p class="text-xs text-gray-400">{{ $vendor->email }}</p>@endif
                </td>
                <td class="px-6 py-4">
                    <p class="text-sm text-gray-700">{{ $vendor->user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $vendor->user->email }}</p>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $vendor->products_count }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $vendor->statusBadge() }}">{{ ucfirst($vendor->status) }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end items-center space-x-3">
                        <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                        @if($vendor->status === 'pending')
                        <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                            @csrf
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium">Approve</button>
                        </form>
                        <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" onsubmit="return confirm('Reject this application?')">
                            @csrf
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Reject</button>
                        </form>
                        @elseif($vendor->status === 'approved')
                        <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST" onsubmit="return confirm('Suspend this vendor?')">
                            @csrf
                            <button class="text-orange-500 hover:text-orange-700 text-sm font-medium">Suspend</button>
                        </form>
                        @elseif(in_array($vendor->status, ['suspended','rejected']))
                        <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                            @csrf
                            <button class="text-green-600 hover:text-green-800 text-sm font-medium">Re-approve</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No vendors found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($vendors->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $vendors->links() }}</div>
    @endif
</div>
@endsection
