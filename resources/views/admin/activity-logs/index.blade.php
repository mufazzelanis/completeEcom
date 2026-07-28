@extends('layouts.admin')
@section('title', 'Activity Log')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Activity Log</h1>
        <p class="text-sm text-gray-500 mt-0.5">Who logged in, viewed, and did what — across customers and admins</p>
    </div>
    <span class="text-sm text-gray-400">{{ $logs->total() }} total entries</span>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Logins Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['logins_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Product Views Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['product_views_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Cart Adds Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['cart_adds_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Order Views Today</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['order_views_today'] }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..."
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Event</label>
            <select name="event_type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Events</option>
                @foreach($eventTypes as $e)
                <option value="{{ $e }}" {{ request('event_type') === $e ? 'selected' : '' }}>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Role</label>
            <select name="role" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">User</label>
            <select name="user_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Users</option>
                @foreach($logUsers as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
            <a href="{{ route('admin.activity-logs.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm hover:bg-gray-200 transition">Clear</a>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Time</th>
                <th class="px-5 py-3 text-left">User</th>
                <th class="px-5 py-3 text-left">Event</th>
                <th class="px-5 py-3 text-left">Description</th>
                <th class="px-5 py-3 text-left">Device</th>
                <th class="px-5 py-3 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3 whitespace-nowrap">
                    <p class="font-medium text-gray-800 text-xs">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('M d, Y') }}</p>
                    <p class="text-gray-400 text-xs">{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('H:i:s') }}</p>
                </td>
                <td class="px-5 py-3">
                    @if($log->user_name)
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-indigo-600 text-xs font-bold">{{ strtoupper(substr($log->user_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="text-gray-700 text-xs font-medium leading-tight">{{ $log->user_name }}</p>
                            @if($log->user_role)
                            <span class="inline-block mt-0.5 px-1.5 py-0 rounded-full text-[10px] font-medium {{ \App\Models\User::roleBadgeClass($log->user_role) }}">
                                {{ \App\Models\User::roleLabel($log->user_role) }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @else
                    <span class="text-gray-400 text-xs italic">Guest</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @php
                        $color = match(true) {
                            $log->event_type === 'login' => 'bg-green-100 text-green-700',
                            $log->event_type === 'logout' => 'bg-gray-100 text-gray-600',
                            str_starts_with($log->event_type, 'product.') => 'bg-blue-100 text-blue-700',
                            str_starts_with($log->event_type, 'order.') => 'bg-purple-100 text-purple-700',
                            $log->event_type === 'cart.add' => 'bg-orange-100 text-orange-700',
                            $log->event_type === 'cart.remove' => 'bg-red-100 text-red-700',
                            str_contains($log->event_type, 'deleted') => 'bg-red-100 text-red-700',
                            str_contains($log->event_type, 'created') => 'bg-green-100 text-green-700',
                            str_contains($log->event_type, 'updated') => 'bg-blue-100 text-blue-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                        {{ $log->event_type }}
                    </span>
                    @if($log->source === 'audit')
                    <span class="block text-[10px] text-gray-400 mt-0.5">admin action</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-700 max-w-xs">
                    <p class="truncate text-xs">{{ $log->description }}</p>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">
                    @if($log->device)
                        {{ $log->device }}@if($log->browser) · {{ $log->browser }}@endif
                        @if($log->platform)<span class="block text-gray-400">{{ $log->platform }}</span>@endif
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <p class="text-gray-400">No activity found.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
