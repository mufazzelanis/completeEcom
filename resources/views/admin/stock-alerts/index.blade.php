@extends('layouts.admin')
@section('title', 'Stock Alerts')

@section('content')
<style>
    .sa-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    @media (max-width: 639px) { .sa-cards { grid-template-columns: minmax(0, 1fr); } }
    .sa-xs { font-size: 11px; }
</style>
@php $site = setting('site_name', config('app.name')); @endphp

<div class="mb-6">
    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Stock Alerts</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Shoppers who asked to be told when an out-of-stock product is back — real demand to restock for.</p>
</div>

<div class="sa-cards mb-6">
    @foreach([
        ['People waiting', $counts['waiting'], '#6366f1', '🔔'],
        ['Products they want', $counts['products'], '#f59e0b', '📦'],
        ['Told this month', $counts['notified'], '#10b981', '✅'],
    ] as [$label, $value, $color, $icon])
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl" style="background: {{ $color }}1a;">{{ $icon }}</div>
            <div>
                <p class="text-2xl font-extrabold tabular-nums" style="color: {{ $color }};">{{ number_format($value) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex gap-1 bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-1.5">
        @foreach(['waiting' => 'Waiting', 'notified' => 'Notified'] as $key => $label)
            <a href="{{ route('admin.stock-alerts.index', ['status' => $key]) }}" class="px-4 py-2 rounded-xl text-sm font-semibold {{ $status === $key ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">{{ $label }}</a>
        @endforeach
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400">
        @if($smsReady) ✉️ Waiting shoppers get an SMS automatically once the product is back in stock.
        @else ✉️ No SMS gateway yet — when a product is back, message them with the WhatsApp buttons below.@endif
    </p>
</div>

<div class="space-y-4">
    @forelse($groups as $g)
        @php
            $p = $products[$g->product_id] ?? null;
            $inStock = $p && $p->available_stock > 0;
            $people = $alerts[$g->product_id] ?? collect();
            $waText = $p ? "আসসালামু আলাইকুম! সুখবর — আপনি যে পণ্যটির জন্য অপেক্ষা করছিলেন, {$p->name}, এখন {$site}-এ আবার স্টকে আছে। অর্ডার করুন: " . route('products.show', $p) : '';
        @endphp
        @continue(!$p)
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-4 sm:p-5" style="border-left: 4px solid {{ $inStock ? '#10b981' : '#f59e0b' }};">
            <div class="flex flex-wrap items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-800 overflow-hidden flex-shrink-0">
                    @if($p->image)<img src="{{ image_thumb($p->image, 200) }}" alt="" class="w-full h-full object-contain" loading="lazy">@endif
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('admin.products.edit', $p->id) }}" class="font-semibold text-gray-900 dark:text-gray-100 hover:underline">{{ $p->name }}</a>
                    <p class="text-sm mt-0.5">
                        @if($inStock)
                            <span class="font-semibold" style="color:#059669;">✓ Back in stock ({{ $p->available_stock }}) — tell them!</span>
                        @else
                            <span class="font-semibold" style="color:#d97706;">Out of stock</span>
                        @endif
                        <span class="text-gray-500 dark:text-gray-400"> · {{ $g->people }} {{ \Illuminate\Support\Str::plural('person', $g->people) }} {{ $status === 'waiting' ? 'waiting' : 'notified' }}</span>
                    </p>
                </div>
                @if($status === 'waiting')
                    <form method="POST" action="{{ route('admin.stock-alerts.notified', $p->id) }}">
                        @csrf
                        <button class="px-3 py-2 rounded-xl text-xs font-semibold" style="background:#10b9811a;color:#059669;" title="After you've messaged them">Mark all notified</button>
                    </form>
                @endif
            </div>

            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-wrap gap-2">
                @foreach($people->take(30) as $a)
                    <div class="inline-flex items-center gap-1.5 rounded-xl bg-gray-50 dark:bg-gray-800 pl-3 pr-1 py-1 text-sm">
                        <span class="font-mono text-gray-800 dark:text-gray-200">{{ $a->phone }}</span>
                        <span class="sa-xs text-gray-400">{{ $a->created_at->diffForHumans(null, true) }}</span>
                        @if($inStock && $status === 'waiting')
                            <a href="https://wa.me/{{ $a->waPhone() }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" class="px-2 py-1 rounded-lg sa-xs font-bold text-white" style="background:#25d366;">WhatsApp</a>
                        @endif
                        <a href="tel:{{ $a->phone }}" class="px-2 py-1 rounded-lg sa-xs font-bold" style="background:#6366f11a;color:#6366f1;">Call</a>
                        <form method="POST" action="{{ route('admin.stock-alerts.destroy', $a) }}">
                            @csrf @method('DELETE')
                            <button class="px-1.5 text-gray-300 hover:text-red-500" title="Remove">✕</button>
                        </form>
                    </div>
                @endforeach
                @if($people->count() > 30)<span class="text-xs text-gray-400 self-center">+{{ $people->count() - 30 }} more</span>@endif
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-12 text-center">
            <div class="text-5xl mb-3">🔔</div>
            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $status === 'waiting' ? 'Nobody is waiting right now' : 'No one notified yet' }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Out-of-stock product pages show a "Notify me when it's back" box — requests appear here.</p>
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $groups->links() }}</div>
@endsection
