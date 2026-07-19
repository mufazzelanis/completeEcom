<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - @yield('title', 'Dashboard') | {{ setting('site_name', 'ShopVista') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" x-cloak
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col flex-shrink-0 overflow-y-auto transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:z-auto"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <!-- Logo -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
            <div class="flex items-center space-x-3">
                @php $logoUrl = setting_file_url('site_logo'); @endphp
                @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ setting('site_name','ShopVista') }}" class="h-8 max-w-[120px] object-contain">
                @else
                <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold">{{ strtoupper(substr(setting('site_name','S'),0,1)) }}</span>
                </div>
                @endif
                <div>
                    <div class="text-white font-bold text-sm">{{ setting('site_name', 'ShopVista') }}</div>
                    <div class="text-gray-400 text-xs">Admin Panel</div>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            {{-- Catalog --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Catalog</p>
            </div>
            <a href="{{ route('admin.categories.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.categories.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Categories</span>
            </a>
            <a href="{{ route('admin.subcategories.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.subcategories.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"/></svg>
                <span>Subcategories</span>
            </a>
            <a href="{{ route('admin.reviews.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reviews.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <span>Reviews</span>
            </a>

            {{-- Products --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Products</p>
            </div>
            <a href="{{ route('admin.products.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.show') || request()->routeIs('admin.products.edit') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>All Products</span>
            </a>
            <a href="{{ route('admin.products.create') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.create') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>
            <a href="{{ route('admin.sale-products.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.sale-products.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Sale Products</span>
            </a>
            <a href="{{ route('admin.products.bulk-upload') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.products.bulk-upload') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Bulk Upload</span>
            </a>
            <a href="{{ route('admin.brands.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.brands.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Brands</span>
            </a>
            <a href="{{ route('admin.attributes.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.attributes.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Attributes</span>
            </a>
            <a href="{{ route('admin.tags.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.tags.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Tags</span>
            </a>

            {{-- Inventory --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Inventory</p>
            </div>
            <a href="{{ route('admin.stock-management.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-management.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Stock Management</span>
            </a>
            <a href="{{ route('admin.stock-adjustments.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-adjustments.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Stock History</span>
            </a>
            @php $lowStockCount = \App\Models\Product::where('is_active', true)->where('stock', 0)->count(); @endphp
            <a href="{{ route('admin.low-stock.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.low-stock.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="flex-1">Low Stock Alerts</span>
                @if($lowStockCount > 0)<span class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $lowStockCount }}</span>@endif
            </a>
            <a href="{{ route('admin.warehouses.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.warehouses.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Warehouses</span>
            </a>
            <a href="{{ route('admin.warehouse-stock.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.warehouse-stock.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span>Multi-Warehouse</span>
            </a>
            <a href="{{ route('admin.stock-transfers.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.stock-transfers.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Stock Transfers</span>
            </a>
            <a href="{{ route('admin.batches.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.batches.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Batch / Lot</span>
            </a>
            <a href="{{ route('admin.barcodes.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.barcodes.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Barcodes</span>
            </a>
            <a href="{{ route('admin.sku-management.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.sku-management.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>SKU Management</span>
            </a>
            <a href="{{ route('admin.vendors.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.vendors.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4"/></svg>
                <span>Vendors</span>
                @php $pendingVendors = \App\Models\Vendor::where('status','pending')->count(); @endphp
                @if($pendingVendors > 0)
                <span class="ml-auto bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">{{ $pendingVendors > 9 ? '9+' : $pendingVendors }}</span>
                @endif
            </a>
            <a href="{{ route('admin.suppliers.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.suppliers.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Suppliers</span>
            </a>
            <a href="{{ route('admin.purchases.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.purchases.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Purchases</span>
            </a>

            {{-- Sales --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Sales</p>
            </div>
            <a href="{{ route('admin.orders.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.orders.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Orders</span>
            </a>
            <a href="{{ route('admin.returns.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.returns.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                <span>Returns</span>
                @php $pendingReturns = \App\Models\ProductReturn::where('status','pending')->count(); @endphp
                @if($pendingReturns > 0)
                <span class="ml-auto bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">{{ $pendingReturns > 9 ? '9+' : $pendingReturns }}</span>
                @endif
            </a>
            <a href="{{ route('admin.payments.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.payments.index') || request()->routeIs('admin.payments.show') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Payments</span>
            </a>
            <a href="{{ route('admin.payment-methods.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.payment-methods.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Payment Methods</span>
            </a>
            <a href="{{ route('admin.coupons.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.coupons.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Coupons</span>
            </a>

            {{-- Reports --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Reports</p>
            </div>
            <a href="{{ route('admin.reports.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.index') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('admin.reports.sales') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.sales') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Sales Report</span>
            </a>
            <a href="{{ route('admin.reports.revenue') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.revenue') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Revenue</span>
            </a>
            <a href="{{ route('admin.reports.products') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.products') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Products</span>
            </a>
            <a href="{{ route('admin.reports.customers') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.customers') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Customers</span>
            </a>
            <a href="{{ route('admin.reports.inventory') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.reports.inventory') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                <span>Inventory</span>
            </a>

            {{-- CMS --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">CMS</p>
            </div>
            <a href="{{ route('admin.blog.posts.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.blog.posts.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                <span>Blog Posts</span>
            </a>
            <a href="{{ route('admin.blog.categories.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.blog.categories.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Blog Categories</span>
            </a>
            <a href="{{ route('admin.banners.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.banners.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Banners</span>
            </a>
            <a href="{{ route('admin.pages.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.pages.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Pages</span>
            </a>
            <a href="{{ route('admin.faqs.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.faqs.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>FAQs</span>
            </a>

            {{-- Marketing --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Marketing</p>
            </div>
            <a href="{{ route('admin.marketing.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.marketing.index') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('admin.flash-sales.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.flash-sales.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="flex-1">Flash Sales</span>
                @php $liveFlash = \App\Models\FlashSale::where('is_active',true)->where('starts_at','<=',now())->where('ends_at','>=',now())->count(); @endphp
                @if($liveFlash > 0)<span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>@endif
            </a>
            <a href="{{ route('admin.promo-codes.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.promo-codes.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span>Promo Codes</span>
            </a>
            <a href="{{ route('admin.bundles.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.bundles.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Bundles</span>
            </a>
            <a href="{{ route('admin.cross-sell.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.cross-sell.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Cross-Sell / Upsell</span>
            </a>
            <a href="{{ route('admin.referrals.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.referrals.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="flex-1">Referrals</span>
                @php $pendingRef = \App\Models\ReferralReward::where('status','pending')->count(); @endphp
                @if($pendingRef > 0)<span class="bg-orange-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $pendingRef }}</span>@endif
            </a>
            <a href="{{ route('admin.email-campaigns.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.email-campaigns.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>Email Campaigns</span>
            </a>

            {{-- Notifications --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Notifications</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.notifications.index') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Overview</span>
            </a>
            <a href="{{ route('admin.notifications.templates') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.notifications.templates*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Templates</span>
            </a>
            <a href="{{ route('admin.notifications.logs') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.notifications.logs') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Delivery Logs</span>
            </a>
            <a href="{{ route('admin.notifications.settings') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.notifications.settings') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Settings</span>
            </a>

            {{-- People --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">People</p>
            </div>
            <a href="{{ route('admin.users.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Users</span>
            </a>
            <a href="{{ route('admin.roles.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.roles.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Roles & Permissions</span>
            </a>

            {{-- Security --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Security</p>
            </div>
            @php $fraudFlagged = \App\Models\Order::where('is_fraud_flagged', true)->count(); @endphp
            <a href="{{ route('admin.orders.index', ['fraud' => 1]) }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.orders.index') && request('fraud') ? 'bg-red-700 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span class="flex-1">Fraud Alerts</span>
                @if($fraudFlagged > 0)<span class="bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">{{ $fraudFlagged }}</span>@endif
            </a>
            <a href="{{ route('admin.audit-logs.index') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Audit Logs</span>
            </a>

            {{-- Settings --}}
            <div class="pt-4 pb-1">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3">Settings</p>
            </div>
            <a href="{{ route('admin.settings.show', 'general') }}"
                class="flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.settings.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>General Settings</span>
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
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm px-4 sm:px-6 py-3 flex items-center gap-3 flex-shrink-0">
            <!-- Mobile hamburger -->
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 -ml-1 p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <h1 class="text-lg font-semibold text-gray-800 flex-shrink-0 hidden sm:block">@yield('title', 'Dashboard')</h1>

            <!-- Global Admin Quick Search -->
            <div class="relative flex-1 max-w-sm hidden sm:block" x-data="{
                query: '',
                results: [],
                open: false,
                async fetchSuggestions() {
                    if (this.query.length < 2) { this.open = false; return; }
                    const res = await fetch('{{ route('admin.search.suggest') }}?q=' + encodeURIComponent(this.query));
                    const data = await res.json();
                    this.results = data.products;
                    this.open = this.results.length > 0;
                }
            }" @click.outside="open = false">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text"
                        x-model="query"
                        @input.debounce.300ms="fetchSuggestions()"
                        @focus="query.length > 1 && fetchSuggestions()"
                        @keydown.escape="open = false"
                        placeholder="Quick search products..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 bg-gray-50"
                        autocomplete="off">
                </div>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 right-0 bg-white shadow-xl rounded-xl border border-gray-100 z-50 mt-1 overflow-hidden">
                    <template x-for="product in results" :key="product.url">
                        <a :href="product.url" @click="open = false; query = ''"
                           class="flex items-center px-3 py-2.5 hover:bg-orange-50 gap-3">
                            <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <img x-show="product.image" :src="product.image" class="w-full h-full object-cover">
                                <svg x-show="!product.image" class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="product.name"></p>
                                <p class="text-xs text-gray-500" x-text="product.sku + ' · ' + product.price"></p>
                            </div>
                            <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                  x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                        </a>
                    </template>
                    <div class="px-3 py-2 border-t border-gray-100 bg-gray-50">
                        <a :href="'{{ route('admin.products.index') }}?search=' + encodeURIComponent(query)"
                           class="text-xs text-orange-600 hover:text-orange-800 font-medium">
                            Search all products for "<span x-text="query"></span>" &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 ml-auto flex-shrink-0">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-sm hidden md:block">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="bg-red-100 text-red-700 px-3 py-1 rounded-lg text-sm hidden md:block">{{ session('error') }}</div>
                @endif
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                        <span class="text-orange-600 font-semibold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <span class="text-sm font-medium text-gray-700 hidden sm:inline">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Mobile Flash Messages -->
        @if(session('success') || session('error'))
        <div class="sm:hidden px-4 pt-2">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm">{{ session('error') }}</div>
            @endif
        </div>
        @endif

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>