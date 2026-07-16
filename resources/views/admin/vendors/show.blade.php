@extends('layouts.admin')
@section('title', 'Vendor Details')

@section('content')
<a href="{{ route('admin.vendors.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>Back to Vendors</span>
</a>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-indigo-600 font-bold text-2xl">{{ strtoupper(substr($vendor->business_name, 0, 1)) }}</span>
        </div>
        <h2 class="text-lg font-bold text-gray-900">{{ $vendor->business_name }}</h2>
        <p class="text-gray-500 text-sm">{{ $vendor->email }}</p>
        <p class="text-gray-500 text-sm mt-1">{{ $vendor->phone }}</p>
        <div class="mt-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $vendor->statusBadge() }}">{{ ucfirst($vendor->status) }}</span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Applied {{ $vendor->created_at->format('M d, Y') }}</p>
        @if($vendor->approved_at)
        <p class="text-xs text-gray-400">Approved {{ $vendor->approved_at->format('M d, Y') }}@if($vendor->approver) by {{ $vendor->approver->name }}@endif</p>
        @endif

        <div class="mt-5 flex flex-col gap-2">
            @if($vendor->status === 'pending')
            <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Approve</button>
            </form>
            <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" onsubmit="return confirm('Reject this application?')">
                @csrf
                <button class="w-full bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-100 transition">Reject</button>
            </form>
            @elseif($vendor->status === 'approved')
            <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST" onsubmit="return confirm('Suspend this vendor?')">
                @csrf
                <button class="w-full bg-orange-50 text-orange-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-100 transition">Suspend</button>
            </form>
            @elseif(in_array($vendor->status, ['suspended', 'rejected']))
            <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">
                @csrf
                <button class="w-full bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">Re-approve</button>
            </form>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-3">Business Details</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $vendor->description ?: 'No description provided.' }}</p>
            @if($vendor->status === 'rejected' && $vendor->rejection_reason)
            <p class="text-sm text-red-600 mt-3"><strong>Rejection reason:</strong> {{ $vendor->rejection_reason }}</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-1">Products</h2>
            <p class="text-sm text-gray-500">{{ $vendor->products_count }} product(s) listed under this vendor.</p>
        </div>
    </div>
</div>
@endsection
