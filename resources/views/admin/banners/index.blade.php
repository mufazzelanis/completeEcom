@extends('layouts.admin')
@section('title', 'Banners')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">{{ $banners->flatten()->count() }} banners</p>
    <a href="{{ route('admin.banners.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Banner
    </a>
</div>

@if($banners->isEmpty())
<div class="bg-white rounded-2xl shadow-sm px-6 py-12 text-center text-gray-400">
    No banners yet. <a href="{{ route('admin.banners.create') }}" class="text-indigo-600">Add one</a>.
</div>
@else
<div class="space-y-6">
    @foreach($banners as $position => $group)
    @php
        // Pre-resolved into plain, display-ready values (formatted schedule text, absolute
        // URLs) so the Alpine side is just "render this array" — no route()/Storage::url()
        // reconstruction or date formatting needed once this hits the browser.
        $items = $group->map(fn ($banner) => [
            'id'          => $banner->id,
            'title'       => $banner->title,
            'subtitle'    => $banner->subtitle,
            'image_url'   => $banner->image ? \Illuminate\Support\Facades\Storage::url($banner->image) : null,
            'is_active'   => $banner->is_active,
            'schedule'    => ($banner->starts_at || $banner->ends_at)
                ? ($banner->starts_at?->format('d/m/y') ?? '∞') . ' – ' . ($banner->ends_at?->format('d/m/y') ?? '∞')
                : null,
            'edit_url'    => route('admin.banners.edit', $banner),
            'destroy_url' => route('admin.banners.destroy', $banner),
            'toggle_url'  => route('admin.banners.toggle', $banner),
        ])->values();
    @endphp
    {{-- Reorder is scoped to this one position group — the storefront itself only ever
         orders banners within a single position (see HomeController), so dragging across
         groups would change nothing visible and just be confusing. A drop persists
         instantly via fetch(); a failed save reverts the row order rather than leaving the
         UI showing an order the database doesn't actually have. --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden" x-data="{
            items: {{ Js::from($items) }},
            dragIndex: null,
            saving: false,
            savedPulse: false,
            onDrop(i) {
                if (this.dragIndex === null || this.dragIndex === i) { this.dragIndex = null; return; }
                const moved = this.items.splice(this.dragIndex, 1)[0];
                this.items.splice(i, 0, moved);
                this.dragIndex = null;
                this.persist();
            },
            persist() {
                const prevItems = this.items.slice();
                this.saving = true;
                fetch(@js(route('admin.banners.reorder')), {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ ids: this.items.map(b => b.id) }),
                })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(() => {
                    this.savedPulse = true;
                    setTimeout(() => this.savedPulse = false, 1200);
                    if (typeof showToast === 'function') showToast('Order updated.');
                })
                .catch(() => {
                    this.items = prevItems;
                    if (typeof showToast === 'function') showToast('Could not save the new order — please try again.', 'error');
                })
                .finally(() => this.saving = false);
            },
         }">
        <div class="px-6 py-3.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium capitalize">{{ $position }}</span>
                <span class="text-xs text-gray-400">{{ $group->count() }} {{ Str::plural('banner', $group->count()) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs" :class="saving || savedPulse ? 'text-indigo-500' : 'text-gray-300'">
                <svg x-show="saving" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <svg x-show="!saving && savedPulse" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span x-text="saving ? 'Saving order…' : (savedPulse ? 'Saved' : (items.length > 1 ? 'Drag ⋮⋮ to reorder' : ''))"></span>
            </div>
        </div>

        <div class="divide-y divide-gray-50">
            <template x-for="(banner, i) in items" :key="banner.id">
                <div class="flex items-center gap-4 px-6 py-3 transition banner-row"
                     :class="dragIndex === i ? 'opacity-40' : 'hover:bg-gray-50'">
                    <div class="cursor-move text-gray-300 hover:text-gray-500 flex-shrink-0" draggable="true"
                         @dragstart="dragIndex = i" @dragover.prevent @drop.prevent="onDrop(i)" title="Drag to reorder">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </div>

                    <div class="w-20 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        <img :src="banner.image_url" class="w-full h-full object-cover" x-show="banner.image_url">
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm truncate" x-text="banner.title || '(untitled)'"></p>
                        <p class="text-xs text-gray-400 truncate" x-show="banner.subtitle" x-text="banner.subtitle"></p>
                    </div>

                    <div class="text-xs text-gray-500 w-36 flex-shrink-0 text-center hidden sm:block">
                        <span x-show="banner.schedule" x-text="banner.schedule"></span>
                        <span class="text-gray-300" x-show="!banner.schedule">Always</span>
                    </div>

                    <div class="flex-shrink-0">
                        <form :action="banner.toggle_url" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium hover:opacity-80 transition"
                                :class="banner.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                x-text="banner.is_active ? 'Active' : 'Inactive'"></button>
                        </form>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0 w-28 justify-end">
                        <a :href="banner.edit_url" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                        <form :action="banner.destroy_url" method="POST" onsubmit="return uiConfirm(event, 'Delete this banner?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </div>
    @endforeach
</div>
@endif

<style>
/* Keeps rows from snapping instantly when the drop lands and every sibling shifts a slot. */
.banner-row { transition: opacity 0.15s ease, background-color 0.15s ease; }
</style>
@endsection
