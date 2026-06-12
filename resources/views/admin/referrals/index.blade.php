@extends('layouts.admin')
@section('title', 'Referral Program')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-800">Referral Program</h1>
    <p class="text-sm text-gray-500 mt-0.5">Track customer referrals and manage reward payouts</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    @foreach([
        ['Total Codes', number_format($stats['total_codes']), 'text-gray-700'],
        ['Active Referrers', number_format($stats['active_codes']), 'text-indigo-600'],
        ['Pending Rewards', number_format($stats['pending_count']), 'text-orange-600'],
        ['Total Earned', '৳'.number_format($stats['total_earned'],0), 'text-green-600'],
        ['Total Paid Out', '৳'.number_format($stats['total_paid'],0), 'text-blue-600'],
    ] as [$label, $value, $color])
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-2 gap-6">
    {{-- Pending Rewards --}}
    @if($pendingRewards->count() > 0)
    <div class="col-span-2 bg-white rounded-2xl shadow-sm overflow-hidden border-l-4 border-orange-400">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Pending Rewards ({{ $pendingRewards->count() }})
            </h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Referrer</th>
                <th class="px-5 py-3 text-left">New Customer</th>
                <th class="px-5 py-3 text-right">Reward</th>
                <th class="px-5 py-3 text-center">Order</th>
                <th class="px-5 py-3 text-center">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($pendingRewards as $reward)
                <tr class="hover:bg-orange-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800 text-sm">{{ $reward->referrer->name }}</p>
                        <p class="text-xs text-gray-400">{{ $reward->referrer->email }}</p>
                    </td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800 text-sm">{{ $reward->referee->name }}</p>
                        <p class="text-xs text-gray-400">{{ $reward->referee->email }}</p>
                    </td>
                    <td class="px-5 py-3 text-right font-bold text-orange-600">৳{{ number_format($reward->reward_amount) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($reward->order)
                        <a href="{{ route('admin.orders.show', $reward->order) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-mono">{{ $reward->order->order_number }}</a>
                        @else<span class="text-gray-300 text-xs">—</span>@endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <form action="{{ route('admin.referrals.reward', $reward) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button class="text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-full hover:bg-green-200 transition font-semibold">Approve</button>
                            </form>
                            <form action="{{ route('admin.referrals.reward', $reward) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button class="text-xs bg-red-100 text-red-600 px-2.5 py-1 rounded-full hover:bg-red-200 transition font-semibold">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

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
                    <td class="px-6 py-3 text-right font-semibold text-green-600">৳{{ number_format($ref->total_earned) }}</td>
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
