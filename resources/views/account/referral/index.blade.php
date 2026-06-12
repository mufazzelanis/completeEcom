@extends('layouts.account')
@section('title', 'Referral Program')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-5">Referral Program</h1>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
    @foreach([
        ['Total Referrals', $stats['total_uses'], 'text-indigo-600', 'bg-indigo-50'],
        ['Total Earned', '৳'.number_format($stats['total_earned']), 'text-green-600', 'bg-green-50'],
        ['Pending Rewards', $stats['pending'], 'text-orange-600', 'bg-orange-50'],
        ['Total Paid', '৳'.number_format($stats['paid']), 'text-blue-600', 'bg-blue-50'],
    ] as [$label, $value, $color, $bg])
    <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
        <p class="text-xl font-bold {{ $color }}">{{ $value }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- Referral Code --}}
<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white mb-5"
     x-data="{ copied: false }">
    <h2 class="font-bold text-lg mb-1">Your Referral Code</h2>
    <p class="text-indigo-100 text-sm mb-4">Share this code with friends. When they make their first order, you both earn rewards!</p>
    <div class="flex items-center gap-3 bg-white/20 rounded-xl px-4 py-3">
        <span class="font-mono text-xl font-bold tracking-widest flex-1" id="ref-code">{{ $referral->code }}</span>
        <button @click="navigator.clipboard.writeText('{{ $referral->code }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            class="bg-white text-indigo-600 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition flex-shrink-0">
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak>Copied!</span>
        </button>
    </div>
    <div class="mt-3 flex items-center gap-2">
        <p class="text-xs text-indigo-100">Share link:</p>
        <span class="text-xs text-white font-mono bg-white/20 px-2 py-1 rounded truncate max-w-xs" id="ref-link">{{ url('/') }}?ref={{ $referral->code }}</span>
        <button @click="navigator.clipboard.writeText('{{ url('/') }}?ref={{ $referral->code }}')"
            class="text-xs text-indigo-200 hover:text-white transition">Copy</button>
    </div>
</div>

{{-- Reward History --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Referral History</h2>
    </div>
    @if($rewards->isEmpty())
    <div class="px-5 py-12 text-center text-gray-400 text-sm">No referrals yet. Start sharing your code!</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
            <th class="px-5 py-3 text-left">Friend</th>
            <th class="px-5 py-3 text-right">Reward</th>
            <th class="px-5 py-3 text-center">Status</th>
            <th class="px-5 py-3 text-center">Date</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($rewards as $reward)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <p class="font-medium text-gray-800">{{ $reward->referee->name }}</p>
                    <p class="text-xs text-gray-400">{{ $reward->referee->email }}</p>
                </td>
                <td class="px-5 py-3 text-right font-bold text-green-600">৳{{ number_format($reward->reward_amount) }}</td>
                <td class="px-5 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $reward->status_badge }}">{{ ucfirst($reward->status) }}</span>
                </td>
                <td class="px-5 py-3 text-center text-xs text-gray-400">{{ $reward->created_at->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $rewards->links() }}</div>
    @endif
</div>
@endsection
