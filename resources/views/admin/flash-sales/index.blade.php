@extends('layouts.admin')
@section('title', 'Flash Sales')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Flash Sales</h1>
        <p class="text-sm text-gray-500 mt-0.5">Time-limited deals with live countdown timers</p>
    </div>
    <a href="{{ route('admin.flash-sales.create') }}" class="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Flash Sale
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Sale</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Products</th>
                <th class="px-6 py-3 text-left">Schedule</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($sales as $sale)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-8 rounded-full" style="background:{{ $sale->banner_color }}"></div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $sale->name }}</p>
                            @if($sale->banner_text)<p class="text-xs text-gray-400 mt-0.5">{{ $sale->banner_text }}</p>@endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $sale->status_badge }}">
                        {{ strtoupper($sale->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center font-semibold text-gray-700">{{ $sale->products_count }}</td>
                <td class="px-6 py-4 text-xs text-gray-500">
                    <p>{{ $sale->starts_at->format('M d, Y H:i') }}</p>
                    <p class="text-gray-400">→ {{ $sale->ends_at->format('M d, Y H:i') }}</p>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-3">
                        <a href="{{ route('admin.flash-sales.edit', $sale) }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                        <form action="{{ route('admin.flash-sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('Delete this flash sale?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-16 text-center text-gray-400">No flash sales yet. <a href="{{ route('admin.flash-sales.create') }}" class="text-indigo-600">Create one</a></td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100">{{ $sales->links() }}</div>
</div>
@endsection
