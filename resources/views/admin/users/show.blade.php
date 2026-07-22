@extends('layouts.admin')
@section('title', 'User Profile')

@section('content')
<a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    <span>Back to Users</span>
</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-indigo-600 font-bold text-2xl">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
        </div>
        <h2 class="text-lg font-bold text-gray-900">{{ $user->name }}</h2>
        <p class="text-gray-500 text-sm">{{ $user->email }}</p>
        <p class="text-gray-500 text-sm mt-1">{{ $user->phone }}</p>
        <div class="mt-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-3">Joined {{ $user->created_at->format('M d, Y') }}</p>
        <div class="mt-4 bg-purple-50 rounded-xl px-4 py-3">
            <p class="text-xs font-medium text-purple-500 uppercase tracking-wider">Points Balance</p>
            <p class="text-2xl font-bold text-purple-700">{{ number_format($user->points_balance) }}</p>
        </div>
        <a href="{{ route('admin.users.edit', $user->id) }}" class="block mt-4 text-indigo-600 hover:text-indigo-700 text-sm font-medium">Edit Profile</a>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm">
        <div class="p-6 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Order History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($user->orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-indigo-600 text-sm hover:text-indigo-700">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($order->total) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $order->status_badge }}">{{ $order->status }}</span>
                            </td>
                            <td class="px-6 py-3 text-center text-xs text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm mt-6">
    <div class="p-6 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Points Activity</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="text-xs text-gray-500 uppercase tracking-wider border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left">Type</th>
                    <th class="px-6 py-3 text-right">Points</th>
                    <th class="px-6 py-3 text-left">Description</th>
                    <th class="px-6 py-3 text-center">Order</th>
                    <th class="px-6 py-3 text-center">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pointTransactions as $tx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tx->type_badge }}">{{ $tx->type_label }}</span>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold {{ $tx->points >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points) }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $tx->description }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($tx->order)
                                <a href="{{ route('admin.orders.show', $tx->order) }}" class="text-indigo-600 text-xs hover:text-indigo-700 font-mono">{{ $tx->order->order_number }}</a>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center text-xs text-gray-500">{{ $tx->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">No points activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-100">{{ $pointTransactions->links() }}</div>
</div>
@endsection
