@extends('layouts.account')
@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-800">Welcome back, {{ auth()->user()->name }} 👋</h1>
    <p class="text-sm text-gray-500 mt-0.5">Here's what's happening with your account</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Total Orders', $stats['orders'], route('orders.index'), 'text-indigo-600', 'bg-indigo-50', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
        ['Active Orders', $stats['pending'], route('orders.index'), 'text-orange-600', 'bg-orange-50', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Wishlist', $stats['wishlist'], route('wishlist.index'), 'text-pink-600', 'bg-pink-50', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        ['Reviews', $stats['reviews'], route('account.reviews.index'), 'text-yellow-600', 'bg-yellow-50', 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
    ] as [$label, $value, $link, $color, $bg, $path])
    <a href="{{ $link }}" class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 {{ $bg }} rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 {{ $color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"/></svg>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
        <p class="text-2xl font-bold text-gray-800">{{ $value }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $label }}</p>
    </a>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Recent Orders --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800 text-sm">Recent Orders</h2>
            <a href="{{ route('orders.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all</a>
        </div>
        @forelse($recentOrders as $order)
        <div class="px-5 py-3.5 border-b border-gray-50 hover:bg-gray-50 transition flex items-center gap-4">
            <div class="flex items-center gap-2 overflow-x-auto flex-1 min-w-0">
                @foreach($order->items->take(2) as $item)
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                    @if($item->product?->image)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                    @endif
                </div>
                @endforeach
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $order->created_at->format('M d, Y') }} · {{ $order->items->count() }} item(s)</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span class="text-sm font-semibold text-gray-800">৳{{ number_format($order->total) }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $order->status_badge }} capitalize">{{ $order->status }}</span>
                <a href="{{ route('orders.show', $order) }}" class="text-xs text-indigo-600 hover:text-indigo-800">View</a>
            </div>
        </div>
        @empty
        <div class="px-5 py-12 text-center text-gray-400 text-sm">
            No orders yet. <a href="{{ route('shop.index') }}" class="text-indigo-600">Start shopping</a>
        </div>
        @endforelse
    </div>

    {{-- Notifications + Quick Links --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">Notifications</h2>
                <a href="{{ route('account.notifications') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all</a>
            </div>
            @forelse($notifications as $notif)
            <div class="px-5 py-3 border-b border-gray-50 flex items-start gap-3 {{ !$notif->is_read ? 'bg-indigo-50/40' : '' }}">
                <div class="mt-0.5 flex-shrink-0">{!! $notif->icon !!}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-800">{{ $notif->title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $notif->message }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">No notifications</div>
            @endforelse
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white">
            <p class="text-sm font-semibold mb-1">Refer & Earn</p>
            <p class="text-xs text-indigo-100 mb-3">Share your referral link and earn rewards for every friend who shops.</p>
            <a href="{{ route('account.referral') }}" class="inline-block bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                View Referral Program →
            </a>
        </div>
    </div>
</div>
@endsection
