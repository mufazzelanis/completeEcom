@extends('layouts.seller')
@section('title', 'Seller Dashboard')
@section('pageTitle', 'Dashboard')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Welcome, {{ $vendor->business_name }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">Here's how your shop is doing.</p>
    </div>
    <a href="{{ route('seller.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        + Add Product
    </a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Total Products</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Pending Approval</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Live</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Rejected</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['rejected'] }}</p>
    </div>
</div>


<div class="flex items-center justify-between mb-3 mt-2">
    <h2 class="font-semibold text-gray-800">Sales &amp; Earnings</h2>
    <a href="{{ route('seller.reports.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View report →</a>
</div>
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Items Sold</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $earnings['sold_count'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">On Hold</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">৳{{ number_format($earnings['hold']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Available</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">৳{{ number_format($earnings['available']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Paid Out</p>
        <p class="text-2xl font-bold text-green-600 mt-1">৳{{ number_format($earnings['paid']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Cancelled</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $earnings['cancelled_count'] }}</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Recent Products</h2>
        <a href="{{ route('seller.products.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View all →</a>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($recentProducts as $product)
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</p>
                    <p class="text-xs text-gray-400">৳{{ number_format($product->price) }}</p>
                </div>
            </div>
            @php
                $badge = match($product->approval_status) {
                    'pending' => 'bg-yellow-100 text-yellow-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default => 'bg-green-100 text-green-700',
                };
            @endphp
            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }} flex-shrink-0">{{ ucfirst($product->approval_status) }}</span>
        </div>
        @empty
        <div class="px-5 py-12 text-center text-gray-400 text-sm">No products yet. <a href="{{ route('seller.products.create') }}" class="text-indigo-600">Add your first product</a>.</div>
        @endforelse
    </div>
</div>
@endsection
