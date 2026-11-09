@extends('admin.settings.layout')
@section('settings-title', 'Watermark')

@section('settings-content')
@php
    $logoPath = old('logo', $look['logo']);
    $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
@endphp
<form method="POST" action="{{ route('admin.watermark.update') }}"
      x-data="{
          enabled: {{ old('enabled', $enabled) ? 'true' : 'false' }},
          logo: @js($logoPath), logoUrl: @js($logoUrl),
          position: @js(old('position', $look['position'])), size: {{ (int) old('size', $look['size']) }},
          opacity: {{ (int) old('opacity', $look['opacity']) }}, tone: @js(old('tone', $look['tone'])),
          samples: @js($samples), sample: @js($samples[0] ?? null),
          uploading: false, logoError: '', loading: false, src: '', timer: null,
          refresh() {
              if (!this.logo || !this.sample) return;
              clearTimeout(this.timer);
              this.timer = setTimeout(() => {
                  this.loading = true;
                  this.src = @js(route('admin.watermark.preview')) + '?' + new URLSearchParams({ logo: this.logo, image: this.sample, position: this.position, size: this.size, opacity: this.opacity, tone: this.tone });
              }, 200);
          },
          async upload(file) {
              if (!file) return;
              this.uploading = true; this.logoError = '';
              const body = new FormData();
              body.append('logo', file); body.append('_token', @js(csrf_token()));
              try {
                  const res = await fetch(@js(route('admin.watermark.logo')), { method: 'POST', body, headers: { Accept: 'application/json' } });
                  const json = await res.json();
                  if (!res.ok) throw new Error(json.message || 'Could not use this file.');
                  this.logo = json.path; this.logoUrl = json.url; this.refresh();
              } catch (e) { this.logoError = e.message; }
              this.uploading = false;
          },
      }"
      x-init="refresh(); $watch('position', () => refresh()); $watch('size', () => refresh()); $watch('opacity', () => refresh()); $watch('tone', () => refresh()); $watch('sample', () => refresh())">
@csrf

@if($preparing)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm font-semibold text-amber-900">Preparing your photos: {{ $progress }}%</p>
            <button type="submit" form="wm-publish" class="text-xs font-semibold text-amber-900 underline">Go live now</button>
        </div>
        <div class="mt-2 h-1.5 rounded-full bg-amber-200 overflow-hidden"><div class="h-full bg-amber-600" style="width: {{ $progress }}%"></div></div>
        <p class="text-xs text-amber-800 mt-2">This runs in the background and goes live by itself when finished. {{ $isLive ? 'Customers keep seeing the current watermark until then.' : 'Customers keep seeing plain photos until then.' }}</p>
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
    <div class="flex items-start justify-between gap-4 pb-3 border-b">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Logo on product photos</h2>
            <p class="text-xs text-gray-500 mt-1">Your original photos are never changed. Works for every product, old and new, with nothing to re-upload.</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" x-model="enabled" class="sr-only peer" aria-label="Turn the watermark on">
            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-orange-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
        </label>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <input type="hidden" name="logo" :value="logo">
                <div class="flex items-center gap-3">
                    <div class="w-24 h-16 rounded-lg border bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <template x-if="logoUrl"><img :src="logoUrl" alt="Current logo" class="max-w-full max-h-full object-contain"></template>
                        <template x-if="!logoUrl"><span class="text-xs text-gray-500">None yet</span></template>
                    </div>
                    <div class="min-w-0">
                        <input type="file" accept="image/png,image/webp" @change="upload($event.target.files[0])" class="text-sm w-full" aria-label="Upload logo">
                        <p class="text-xs text-gray-500 mt-1" x-show="!uploading">PNG or WebP with a transparent background.</p>
                        <p class="text-xs text-gray-500 mt-1" x-show="uploading">Uploading…</p>
                    </div>
                </div>
                <p class="text-red-600 text-xs mt-1" x-show="logoError" x-text="logoError"></p>
                @error('logo')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="wm-position">Position</label>
                <select id="wm-position" name="position" x-model="position" class="w-full border rounded-lg px-3 py-2.5 text-sm">
                    @foreach(\App\Support\Watermark::POSITIONS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </div>

            <div>
                <label class="flex justify-between text-sm font-medium text-gray-700 mb-1" for="wm-size"><span>Size</span><span x-text="size + '% of the photo'"></span></label>
                <input id="wm-size" type="range" name="size" min="10" max="80" x-model.number="size" class="w-full accent-orange-600">
            </div>

            <div>
                <label class="flex justify-between text-sm font-medium text-gray-700 mb-1" for="wm-opacity"><span>Strength</span><span x-text="opacity + '%'"></span></label>
                <input id="wm-opacity" type="range" name="opacity" min="3" max="40" x-model.number="opacity" class="w-full accent-orange-600">
                <p class="text-xs text-gray-500 mt-1">6–10% is barely there until someone looks closely.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="wm-tone">Colour</label>
                <select id="wm-tone" name="tone" x-model="tone" class="w-full border rounded-lg px-3 py-2.5 text-sm">
                    @foreach(\App\Support\Watermark::TONES as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </div>

            <div class="space-y-2 pt-1">
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="hidden" name="protect" value="0">
                    <input type="checkbox" name="protect" value="1" {{ old('protect', setting('watermark_protect', '1')) === '1' ? 'checked' : '' }} class="mt-0.5">
                    <span>Hide the original files <span class="block text-xs text-gray-500">Direct links to a photo land on the watermarked copy.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="hidden" name="skip_sellers" value="0">
                    <input type="checkbox" name="skip_sellers" value="1" {{ old('skip_sellers', setting('watermark_skip_sellers', '0')) === '1' ? 'checked' : '' }} class="mt-0.5">
                    <span>Leave sellers' products alone <span class="block text-xs text-gray-500">To skip a single product, use the switch on its edit page.</span></span>
                </label>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">Preview</span>
                <span class="text-xs text-gray-500" x-show="loading">Updating…</span>
            </div>
            <div class="aspect-square rounded-xl border bg-white overflow-hidden flex items-center justify-center">
                <template x-if="src"><img :src="src" x-on:load="loading = false" x-on:error="loading = false" alt="Preview of the watermark on a product photo" class="w-full h-full object-contain"></template>
                <template x-if="!src"><p class="text-sm text-gray-500 px-6 text-center">{{ $samples->isEmpty() ? 'Add a product photo to see a preview.' : 'Upload a logo to see it on a product.' }}</p></template>
            </div>
            @if($samples->count() > 1)
                <div class="flex gap-2 mt-2">
                    @foreach($samples as $i => $path)
                        <button type="button" @click="sample = @js($path)" class="w-14 h-14 rounded-lg border-2 overflow-hidden bg-gray-50 p-1" :class="sample === @js($path) ? 'border-orange-600' : 'border-gray-200'" aria-label="Preview on photo {{ $i + 1 }}">
                            <img src="{{ image_thumb($path, 96) }}" alt="" class="w-full h-full object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border p-4 mt-4 text-xs text-gray-500">
    Small card thumbnails (under 400 px) stay clean because a mark can't be read at that size. Your Google Merchant feed keeps the clean photos, as Google rejects watermarked ones.
</div>

<div class="mt-4 flex justify-end">
    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">Save</button>
</div>
</form>

@if($preparing)
    <form id="wm-publish" method="POST" action="{{ route('admin.watermark.publish') }}">@csrf</form>
@endif
@endsection
