@extends('layouts.admin')
@section('title', 'Landing Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-lg font-semibold text-gray-800">Landing Report</h1>
    <a href="{{ route('admin.landing-pages.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Manage Landing Pages →</a>
</div>

<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Views</p>
        <p class="text-2xl font-semibold text-gray-800">{{ number_format($totals['views']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Orders</p>
        <p class="text-2xl font-semibold text-gray-800">{{ number_format($totals['orders']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total Revenue</p>
        <p class="text-2xl font-semibold text-gray-800">৳{{ number_format($totals['revenue']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Conversion Rate</p>
        <p class="text-2xl font-semibold text-gray-800">{{ $totals['conversion'] }}%</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Landing Page</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Views</th>
                <th class="px-6 py-3 text-right">Orders</th>
                <th class="px-6 py-3 text-right">Revenue</th>
                <th class="px-6 py-3 text-right">Conversion</th>
                <th class="px-6 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($pages as $page)
                @php $conv = $page->views_count > 0 ? round($page->orders_count / $page->views_count * 100, 1) : 0; @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.landing-pages.edit', $page) }}" class="font-medium text-indigo-600 text-sm hover:text-indigo-700">{{ $page->title }}</a>
                        <span class="block text-xs text-gray-400 font-mono">/{{ $page->slug }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium capitalize {{ $page->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $page->status }}</span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ number_format($page->views_count) }}</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ number_format($page->orders_count) }}</td>
                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">৳{{ number_format($page->orders_sum_total ?? 0) }}</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ $conv }}%</td>
                    <td class="px-6 py-4 text-center">
                        <a href="{{ route('admin.orders.index', ['landing_page_id' => $page->id]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View Orders</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No landing pages yet. <a href="{{ route('admin.landing-pages.create') }}" class="text-indigo-600 hover:text-indigo-800">Create one</a>.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
