@extends('layouts.admin')
@section('title', 'CRM · ' . $segment->name)

@php
    use App\Support\CrmUi as Ui;
    $canManage = auth()->user()->hasPermission('crm.manage');
@endphp

@section('content')
@include('admin.crm._tabs')

<div class="mb-4"><a href="{{ route('admin.crm.segments.index') }}" class="text-sm text-gray-500 hover:text-orange-600">← All segments</a></div>

<div class="bg-white rounded-2xl shadow-sm p-5 mb-5 flex flex-wrap items-center gap-4">
    <div class="flex-1 min-w-0">
        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2"><span class="w-3 h-3 rounded-full {{ Ui::dot($segment->color) }}"></span>{{ $segment->name }}</h1>
        @if($segment->description)<p class="text-sm text-gray-500 mt-0.5">{{ $segment->description }}</p>@endif
        <div class="mt-2 flex flex-wrap gap-1">
            @foreach($segment->rules as $r)
                <span class="text-[11px] bg-gray-100 text-gray-600 rounded px-1.5 py-0.5">{{ \App\Services\Crm\CrmSegmentEngine::describe($r) }}</span>
            @endforeach
            <span class="text-[11px] text-gray-400 px-1">({{ $segment->match === 'any' ? 'any rule' : 'all rules' }})</span>
        </div>
    </div>
    <div class="text-center px-4"><div class="text-3xl font-bold text-gray-800">{{ number_format($contacts->total()) }}</div><div class="text-xs text-gray-400">people right now</div></div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.crm.segments.export', $segment) }}" data-turbo="false" class="border border-gray-200 hover:bg-gray-50 px-3.5 py-2 rounded-xl text-sm text-gray-700">Export CSV</a>
        <a href="{{ route('admin.crm.contacts.index', ['segment' => $segment->id]) }}" class="border border-gray-200 hover:bg-gray-50 px-3.5 py-2 rounded-xl text-sm text-gray-700" title="Open in the directory to tag or assign everyone in bulk">Bulk actions</a>
        @if($canManage)<a href="{{ route('admin.crm.segments.edit', $segment) }}" class="bg-gray-800 text-white px-3.5 py-2 rounded-xl text-sm hover:bg-gray-700">Edit rules</a>@endif
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100"><tr class="text-xs text-gray-500 uppercase tracking-wider"><th class="px-4 py-3 text-left">Customer</th><th class="px-4 py-3 text-left">Stage</th><th class="px-4 py-3 text-right">Orders</th><th class="px-4 py-3 text-right">Spent</th><th class="px-4 py-3 text-left">Last order</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($contacts as $c)
                @php $lc = Ui::lifecycle($c->lifecycle_stage); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3"><a href="{{ route('admin.crm.contacts.show', $c) }}" class="flex items-center gap-3"><span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold {{ Ui::avatar($c->name) }}">{{ $c->initials }}</span><span><span class="block font-medium text-gray-800">{{ $c->name }}</span><span class="block text-xs text-gray-400">{{ $c->phone ?: $c->email }}</span></span></a></td>
                    <td class="px-4 py-3"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ Ui::badge($lc['color']) }}">{{ $lc['label'] }}</span></td>
                    <td class="px-4 py-3 text-right">{{ $c->orders_count }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ Ui::money($c->total_spent) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $c->last_order_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-14 text-center text-gray-400">Nobody matches these rules right now.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $contacts->links() }}</div>
@endsection
