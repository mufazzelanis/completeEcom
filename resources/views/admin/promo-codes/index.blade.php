@extends('layouts.admin')
@section('title', 'Promo Codes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Promo Code Campaigns</h1>
        <p class="text-sm text-gray-500 mt-0.5">Bulk-generate unique single-use codes per campaign</p>
    </div>
    <a href="{{ route('admin.promo-codes.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Campaign
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Campaign</th>
                <th class="px-6 py-3 text-center">Discount</th>
                <th class="px-6 py-3 text-center">Generated</th>
                <th class="px-6 py-3 text-center">Used</th>
                <th class="px-6 py-3 text-center">Usage</th>
                <th class="px-6 py-3 text-center">Expires</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($batches as $batch)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <p class="font-medium text-gray-800">{{ $batch->name }}</p>
                    @if($batch->prefix)<p class="text-xs text-gray-400 font-mono mt-0.5">Prefix: {{ $batch->prefix }}-</p>@endif
                    @if($batch->min_order_amount > 0)<p class="text-xs text-gray-400 mt-0.5">Min order: ৳{{ number_format($batch->min_order_amount) }}</p>@endif
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full text-xs font-semibold">
                        {{ $batch->discount_type === 'percentage' ? $batch->discount_value.'%' : '৳'.number_format($batch->discount_value) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center font-semibold text-gray-700">{{ number_format($batch->generated_count) }}</td>
                <td class="px-6 py-4 text-center text-gray-600">{{ number_format($batch->used_count) }}</td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5 w-20">
                            <div class="bg-indigo-500 h-1.5 rounded-full" style="width:{{ $batch->usage_rate }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500">{{ $batch->usage_rate }}%</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-center text-xs text-gray-500">
                    {{ $batch->expires_at ? $batch->expires_at->format('M d, Y') : '—' }}
                    @if($batch->expires_at && $batch->expires_at->isPast())
                    <span class="text-red-400 block">Expired</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('admin.promo-codes.toggle', $batch) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $batch->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $batch->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </form>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.promo-codes.show', $batch) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</a>
                        <a href="{{ route('admin.promo-codes.download', $batch) }}" class="text-green-600 hover:text-green-800 text-xs font-medium">CSV</a>
                        <form action="{{ route('admin.promo-codes.destroy', $batch) }}" method="POST" onsubmit="return confirm('Delete this batch?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-16 text-center text-gray-400">No promo campaigns yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $batches->links() }}</div>
</div>
@endsection
