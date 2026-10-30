@extends('admin.settings.layout')
@section('settings-title', 'Redirects')

@section('settings-content')
{{-- Settings → Redirects (Admin\RedirectController): old addresses Google still shows → the
     page they should land on now, and the 404 log of broken addresses that still get visits.
     Applied by the CustomRedirects middleware before any page loads. --}}
<div x-data="{
        tab: {{ Js::from((string) request('tab', 'redirects')) }},
        source: @js(old('_redirect_id') ? '' : old('source', '')),
        target: @js(old('_redirect_id') ? '' : old('target', '')),
        code: '{{ old('_redirect_id') ? '301' : old('status_code', '301') }}',
        fromLog(path) {
            this.source = path; this.target = ''; this.code = '301';
            this.$refs.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.$nextTick(() => this.$refs.target.focus());
        },
     }" class="space-y-5">

    {{-- Explainer + totals --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h2 class="text-base font-semibold text-gray-900">Redirects</h2>
                <p class="text-sm text-gray-500 mt-1">Send an old address (for example one Google still shows in search results) to the page it should open now. Visitors land on the right page, and with a <strong>301</strong> Google moves the old link's ranking — impressions and clicks — to the new page over the next few weeks.</p>
            </div>
            <div class="flex gap-3 text-center">
                <div class="px-4 py-2 rounded-lg bg-gray-50 border">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totals['redirects']) }}</p>
                    <p class="text-xs text-gray-500">redirects</p>
                </div>
                <div class="px-4 py-2 rounded-lg bg-gray-50 border">
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totals['hits']) }}</p>
                    <p class="text-xs text-gray-500">visits redirected</p>
                </div>
                <div class="px-4 py-2 rounded-lg bg-gray-50 border">
                    <p class="text-lg font-bold {{ $totals['notFound'] ? 'text-orange-600' : 'text-gray-900' }}">{{ number_format($totals['notFound']) }}</p>
                    <p class="text-xs text-gray-500">broken links</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('import_errors'))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
            <p class="font-semibold mb-1">Some lines were skipped:</p>
            <ul class="list-disc pl-5 space-y-0.5">@foreach(session('import_errors') as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Add a redirect --}}
    <form method="POST" action="{{ route('admin.redirects.store') }}" x-ref="form" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
        @csrf
        <h3 class="text-sm font-semibold text-gray-900">Add a redirect</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Old address <span class="text-gray-400 font-normal">(as Google shows it)</span></label>
                <input type="text" name="source" x-model="source" required placeholder="https://mitavin.com/old-page  or  /old-page"
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500 @if(!old('_redirect_id')) @error('source') border-red-400 @enderror @endif">
                @if(!old('_redirect_id')) @error('source')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror @endif
            </div>
            <div x-show="code !== '410'">
                <label class="block text-sm font-medium text-gray-700 mb-1">New address <span class="text-gray-400 font-normal">(where it should go)</span></label>
                <input type="text" name="target" x-model="target" x-ref="target" :required="code !== '410'" placeholder="https://mitavin.com/products/new-name  or  /products/new-name"
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500 @if(!old('_redirect_id')) @error('target') border-red-400 @enderror @endif">
                @if(!old('_redirect_id')) @error('target')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror @endif
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="status_code" x-model="code" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
                    <option value="301">301 — moved permanently (best for SEO)</option>
                    <option value="302">302 — temporary (e.g. a sale page for a few days)</option>
                    <option value="410">410 — gone for good (ask Google to drop it)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note <span class="text-gray-400 font-normal">(optional, only you see it)</span></label>
                <input type="text" name="note" value="{{ old('note') }}" maxlength="255" placeholder="e.g. old WooCommerce product"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-5 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">Add redirect</button>
            <p class="text-xs text-gray-500">The homepage, admin, login, cart and checkout can't be redirected. Chains (A → B → C) are shortened to one hop automatically.</p>
        </div>
    </form>

    {{-- CSV import --}}
    <details class="bg-white rounded-xl shadow-sm border p-6">
        <summary class="text-sm font-semibold text-gray-900 cursor-pointer">Import many at once (CSV)</summary>
        <form method="POST" action="{{ route('admin.redirects.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <p class="text-sm text-gray-500">One redirect per line: <code class="text-xs bg-gray-100 px-1 rounded">old address, new address, 301</code> (the type and a 4th "note" column are optional). A header row is fine. An old address that already has a redirect is updated.</p>
            <input type="file" name="file" accept=".csv,.txt" required class="block text-sm">
            @error('file')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">Import CSV</button>
        </form>
    </details>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b">
        <button type="button" @click="tab = 'redirects'" :class="tab === 'redirects' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px">Redirects ({{ number_format($totals['redirects']) }})</button>
        <button type="button" @click="tab = '404'" :class="tab === '404' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px">404 log — broken links ({{ number_format($totals['notFound']) }})</button>
    </div>

    {{-- Redirects list --}}
    <div x-show="tab === 'redirects'" class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <form method="GET" action="{{ route('admin.redirects.index') }}" class="p-4 border-b flex gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search old or new address…" class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500">
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Search</button>
        </form>

        @if($redirects->isEmpty())
            <p class="p-8 text-center text-sm text-gray-500">{{ $search !== '' ? 'No redirects match your search.' : 'No redirects yet — add one above, or pick a broken link from the 404 log.' }}</p>
        @else
            <div class="divide-y">
                @foreach($redirects as $r)
                    <div x-data="{ editing: {{ $errors->any() && old('_redirect_id') == $r->id ? 'true' : 'false' }} }" class="p-4 {{ $r->is_active ? '' : 'bg-gray-50' }}">
                        <div x-show="!editing" class="flex flex-wrap items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-sm font-mono">
                                    <a href="{{ url($r->source) }}" target="_blank" rel="noopener" class="text-gray-900 hover:text-orange-600 break-all" title="Open the old address (it should redirect)">{{ $r->source }}</a>
                                    <span class="text-gray-400">→</span>
                                    @if($r->status_code === 410)
                                        <span class="text-red-600">gone (410)</span>
                                    @else
                                        <a href="{{ $r->target }}" target="_blank" rel="noopener" class="text-orange-600 hover:underline break-all">{{ \Illuminate\Support\Str::after($r->target, '://') }}</a>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-medium">{{ $r->status_code }}</span>
                                    @unless($r->is_active)<span class="inline-block px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 font-medium">paused</span>@endunless
                                    · {{ number_format($r->hits) }} {{ \Illuminate\Support\Str::plural('visit', $r->hits) }}
                                    · last {{ $r->last_hit_at ? $r->last_hit_at->diffForHumans() : 'never' }}
                                    @if($r->note) · {{ $r->note }}@endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="editing = true" class="px-3 py-1.5 text-xs border rounded-lg text-gray-700 hover:bg-gray-50">Edit</button>
                                <form method="POST" action="{{ route('admin.redirects.destroy', $r) }}" onsubmit="return uiConfirm(event, 'Remove this redirect? The old address will show a 404 again.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-xs border border-red-200 rounded-lg text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </div>

                        <form x-show="editing" x-cloak method="POST" action="{{ route('admin.redirects.update', $r) }}" class="space-y-3"
                              x-data="{ code: '{{ $r->status_code }}' }">
                            @csrf @method('PUT')
                            <input type="hidden" name="_redirect_id" value="{{ $r->id }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text" name="source" value="{{ old('_redirect_id') == $r->id ? old('source') : $r->source }}" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500">
                                <input type="text" name="target" x-show="code !== '410'" value="{{ old('_redirect_id') == $r->id ? old('target') : $r->target }}" class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-orange-500">
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <select name="status_code" x-model="code" class="border rounded-lg px-3 py-2 text-sm">
                                    <option value="301">301 permanent</option>
                                    <option value="302">302 temporary</option>
                                    <option value="410">410 gone</option>
                                </select>
                                <input type="text" name="note" value="{{ $r->note }}" maxlength="255" placeholder="Note" class="flex-1 min-w-[10rem] border rounded-lg px-3 py-2 text-sm">
                                <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="rounded text-orange-600" @checked($r->is_active)> Active
                                </label>
                            </div>
                            @if($errors->any() && old('_redirect_id') == $r->id)
                                <p class="text-xs text-red-600">{{ $errors->first() }}</p>
                            @endif
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-1.5 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">Save</button>
                                <button type="button" @click="editing = false" class="px-4 py-1.5 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t">{{ $redirects->links() }}</div>
        @endif
    </div>

    {{-- 404 log --}}
    <div x-show="tab === '404'" x-cloak class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-4 border-b flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500 max-w-xl">Addresses on your site that ended in "page not found", most visited first — collected automatically. Click <strong>Redirect</strong> to send one to the right page.</p>
            @if($notFound->isNotEmpty())
                <form method="POST" action="{{ route('admin.redirects.404.clear') }}" onsubmit="return uiConfirm(event, 'Clear the whole 404 log?')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs border rounded-lg text-gray-600 hover:bg-gray-50">Clear log</button>
                </form>
            @endif
        </div>
        @if($notFound->isEmpty())
            <p class="p-8 text-center text-sm text-gray-500">No broken links recorded — new ones appear here as visitors hit them.</p>
        @else
            <div class="divide-y">
                @foreach($notFound as $nf)
                    <div class="p-4 flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-mono text-gray-900 break-all">{{ $nf->path }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <strong class="text-gray-700">{{ number_format($nf->hits) }}</strong> {{ \Illuminate\Support\Str::plural('visit', $nf->hits) }}
                                · last {{ $nf->last_hit_at?->diffForHumans() }}
                                @if($nf->last_referer) · from <span title="{{ $nf->last_referer }}">{{ parse_url($nf->last_referer, PHP_URL_HOST) ?: $nf->last_referer }}</span>@endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="tab = 'redirects'; fromLog(@js($nf->path))" class="px-3 py-1.5 text-xs bg-orange-600 text-white rounded-lg hover:bg-orange-700">Redirect</button>
                            <form method="POST" action="{{ route('admin.redirects.404.dismiss', $nf) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs border rounded-lg text-gray-600 hover:bg-gray-50" title="Remove from this list (it comes back if it's visited again)">Dismiss</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
