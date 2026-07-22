@extends('layouts.admin')
@section('title', 'Referral Program')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Referral Program</h1>
        <p class="text-sm text-gray-500 mt-0.5">Track customer referrals and manage reward payouts</p>
    </div>
    <a href="{{ route('admin.referrals.settings') }}" class="text-sm text-indigo-600 hover:underline">Settings</a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    @foreach([
        ['Total Codes', number_format($stats['total_codes']), 'text-gray-700'],
        ['Active Referrers', number_format($stats['active_codes']), 'text-indigo-600'],
        ['Referral Points Given', number_format($stats['total_referral_points']).' pts', 'text-orange-600'],
        ['Total Points Awarded', number_format($stats['total_points_awarded']).' pts', 'text-green-600'],
        ['Total Points Redeemed', number_format($stats['total_points_redeemed']).' pts', 'text-blue-600'],
    ] as [$label, $value, $color])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-2 gap-6">
    {{-- Recent Points Activity --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Recent Points Activity</h2>
            <form action="{{ route('admin.referrals.index') }}" method="GET" class="flex items-center gap-2">
                <input type="text" name="activity_search" value="{{ request('activity_search') }}" placeholder="Search by customer name/email..."
                    class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
                <button type="submit" class="bg-gray-100 text-gray-600 px-3 py-1.5 rounded-xl text-xs hover:bg-gray-200 transition">Search</button>
            </form>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Customer</th>
                <th class="px-5 py-3 text-left">Type</th>
                <th class="px-5 py-3 text-right">Points</th>
                <th class="px-5 py-3 text-left">Description</th>
                <th class="px-5 py-3 text-center">Order</th>
                <th class="px-5 py-3 text-center">Date</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pointActivity as $tx)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800 text-sm">{{ $tx->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $tx->user->email }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tx->type_badge }}">{{ $tx->type_label }}</span>
                    </td>
                    <td class="px-5 py-3 text-right font-bold {{ $tx->points >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points) }}
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500">{{ $tx->description }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($tx->order)
                        <a href="{{ route('admin.orders.show', $tx->order) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-mono">{{ $tx->order->order_number }}</a>
                        @else<span class="text-gray-300 text-xs">—</span>@endif
                    </td>
                    <td class="px-5 py-3 text-center text-xs text-gray-400">{{ $tx->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No points activity yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">{{ $pointActivity->links() }}</div>
    </div>

    {{-- All Referral Codes --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Referral Codes</h2>
            <form action="{{ route('admin.referrals.index') }}" method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name/email/code..."
                    class="border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
                <button type="submit" class="bg-gray-100 text-gray-600 px-3 py-1.5 rounded-xl text-xs hover:bg-gray-200 transition">Search</button>
            </form>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-center">Code</th>
                <th class="px-6 py-3 text-center">Uses</th>
                <th class="px-6 py-3 text-right">Earned</th>
                <th class="px-6 py-3 text-center">Rewards</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($codes as $ref)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <p class="font-medium text-gray-800 text-sm">{{ $ref->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $ref->user->email }}</p>
                    </td>
                    <td class="px-6 py-3 text-center font-mono text-xs font-semibold text-indigo-600 tracking-wider">{{ $ref->code }}</td>
                    <td class="px-6 py-3 text-center font-semibold text-gray-700">{{ $ref->total_uses }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-green-600">{{ number_format($ref->total_earned) }} pts</td>
                    <td class="px-6 py-3 text-center text-xs text-gray-500">{{ $ref->rewards_count }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-16 text-center text-gray-400">No referral codes yet. They're created automatically when customers share their link.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-100">{{ $codes->links() }}</div>
    </div>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
     class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-3 rounded-xl shadow-lg text-sm font-medium">
    {{ session('success') }}
</div>
@endif
@endsection
