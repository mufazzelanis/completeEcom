<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title', 'Dashboard') | ShopVista</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0 overflow-y-auto" x-data="{ open: true }">
        <div class="flex items-center space-x-3 px-6 py-5 border-b border-gray-700">
            <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold">S</span>
            </div>
            <div>
                <div class="text-white font-bold">ShopVista</div>
                <div class="text-gray-400 text-xs">Admin Panel</div>
            </div>
        </div>

        <nav class="flex-1 px-4 py-4 space-y-1">

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            {{-- Catalog --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Catalog</p>
            </div>
            <a href="{{ route('admin.categories.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Categories</span>
            </a>
            <a href="{{ route('admin.subcategories.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.subcategories.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"/></svg>
                <span>Subcategories</span>
            </a>
            <a href="{{ route('admin.reviews.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reviews.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <span>Reviews</span>
            </a>

            {{-- Products --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Products</p>
            </div>
            <a href="{{ route('admin.products.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.show') || request()->routeIs('admin.products.edit') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>All Products</span>
            </a>
            <a href="{{ route('admin.products.create') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.create') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>
            <a href="{{ route('admin.sale-products.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.sale-products.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Sale Products</span>
            </a>
            <a href="{{ route('admin.products.bulk-upload') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.bulk-upload') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Bulk Upload</span>
            </a>
            <a href="{{ route('admin.brands.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.brands.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Brands</span>
            </a>
            <a href="{{ route('admin.attributes.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.attributes.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Attributes</span>
            </a>
            <a href="{{ route('admin.tags.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Tags</span>
            </a>

            {{-- Inventory --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Inventory</p>
            </div>
            <a href="{{ route('admin.stock-management.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-management.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Stock Management</span>
            </a>
            <a href="{{ route('admin.stock-adjustments.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-adjustments.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Stock History</span>
            </a>
            @php $lowStockCount = \App\Models\Product::where('is_active', true)->where('stock', 0)->count(); @endphp
            <a href="{{ route('admin.low-stock.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.low-stock.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="flex-1">Low Stock Alerts</span>
                @if($lowStockCount > 0)<span class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $lowStockCount }}</span>@endif
            </a>
            <a href="{{ route('admin.warehouses.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.warehouses.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Warehouses</span>
            </a>
            <a href="{{ route('admin.warehouse-stock.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.warehouse-stock.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Multi-Warehouse Stock</span>
            </a>
            <a href="{{ route('admin.stock-transfers.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-transfers.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Stock Transfers</span>
            </a>
            <a href="{{ route('admin.batches.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.batches.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Batch / Lot</span>
            </a>
            <a href="{{ route('admin.barcodes.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.barcodes.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Barcode Management</span>
            </a>
            <a href="{{ route('admin.sku-management.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.sku-management.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>SKU Management</span>
            </a>
            <a href="{{ route('admin.suppliers.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.suppliers.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Suppliers</span>
            </a>
            <a href="{{ route('admin.purchases.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.purchases.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Purchases</span>
            </a>

            {{-- Sales --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Sales</p>
            </div>
            <a href="{{ route('admin.orders.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.orders.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Orders</span>
            </a>
            <a href="{{ route('admin.returns.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.returns.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                <span>Returns</span>
                @php $pendingReturns = \App\Models\ProductReturn::where('status','pending')->count(); @endphp
                @if($pendingReturns > 0)
                <span class="ml-auto bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">{{ $pendingReturns > 9 ? '9+' : $pendingReturns }}</span>
                @endif
            </a>
            <a href="{{ route('admin.payments.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.payments.index') || request()->routeIs('admin.payments.show') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Payments</span>
            </a>
            <a href="{{ route('admin.payment-methods.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.payment-methods.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Payment Methods</span>
            </a>
            <a href="{{ route('admin.coupons.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.coupons.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Coupons</span>
            </a>

            {{-- People --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">People</p>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.roles.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.roles.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Roles & Permissions</span>
            </a>

        </nav>

        <div class="px-4 py-4 border-t border-gray-700">
            <a href="{{ route('home') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-gray-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Store</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between flex-shrink-0">
            <h1 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
            <div class="flex items-center space-x-4">
                @if(session('success'))
                    <div class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-sm">{{ session('error') }}</div>
                @endif
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                        <span class="text-indigo-600 font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
