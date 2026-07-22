@extends('layouts.admin')
@section('title', 'Rewards & Points Settings')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Rewards & Points Settings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Configure how customers earn and spend points from referrals and purchases</p>
    </div>
    <a href="{{ route('admin.referrals.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to Referrals</a>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6 max-w-lg space-y-6">
    <form action="{{ route('admin.referrals.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Points per successful referral</label>
            <input type="number" name="reward_amount" step="1" min="0" value="{{ old('reward_amount', $rewardAmount) }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('reward_amount')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-2">Awarded to the referrer as a pending reward when a referred customer completes their first paid order.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Points per purchase</label>
            <input type="number" name="purchase_points" step="1" min="0" value="{{ old('purchase_points', $purchasePoints) }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('purchase_points')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-2">Awarded to any customer automatically the first time their order is marked paid.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Point value when redeemed (৳ per point)</label>
            <input type="number" name="redeem_rate" step="0.01" min="0.01" value="{{ old('redeem_rate', $redeemRate) }}"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('redeem_rate')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-2">How much ৳ discount one point is worth when a customer applies their points at checkout.</p>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
            Save Settings
        </button>
    </form>
</div>
@endsection
