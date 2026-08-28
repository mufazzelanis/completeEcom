@php
    $lp = $landingPage ?? null;
    $existingFields = old('field_label') ? null : ($lp->order_form_fields ?? []);

    // Repeater seed helper — old() input on a validation-retry takes priority over the
    // saved model value, same pattern the Order Form Builder below already established.
    // $fieldMap is ['output_key' => 'input_name'] (matches the controller's normalizeRepeater
    // field maps exactly), $primaryKey picks which column's old() array drives the row count.
    $seed = function (array $fieldMap, string $primaryKey, array $existing = []) {
        $primaryOld = old($fieldMap[$primaryKey]);
        if ($primaryOld === null) {
            return $existing;
        }
        $rows = [];
        foreach ($primaryOld as $i => $_) {
            $row = [];
            foreach ($fieldMap as $outKey => $inputName) {
                $row[$outKey] = old($inputName)[$i] ?? '';
            }
            $rows[] = $row;
        }
        return $rows;
    };
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
    <div class="col-span-2 space-y-5">
        {{-- Basic Info --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
            <h3 class="font-medium text-gray-800">Basic Info</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $lp->title ?? '') }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    URL Slug
                    <span class="text-gray-400 font-normal">— {{ request()->getHost() }}/<span class="text-gray-600">your-slug</span></span>
                </label>
                <input type="text" name="slug" value="{{ old('slug', $lp->slug ?? '') }}" placeholder="{{ $lp ? $lp->slug : 'auto-generated from title if left blank' }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @if($lp)
                <p class="text-xs text-amber-600 mt-1">Changing this moves the live URL — anything already pointing at the old one (ads, printed materials) will break.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Link to Product <span class="text-gray-400 font-normal">(optional)</span></label>
                @php
                    $allProducts = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values();
                    $selectedId = (int) old('product_id', $lp->product_id ?? 0);
                    $selectedName = $allProducts->firstWhere('id', $selectedId)['name'] ?? '';
                @endphp
                {{-- Plain client-side filter over the already-loaded product list (13 today,
                     still fine into the low hundreds) — no new JS library, no new AJAX
                     endpoint, nothing that touches anything outside this one field. --}}
                <div class="relative" x-data="{
                        products: {{ Js::from($allProducts) }},
                        query: {{ Js::from($selectedName) }},
                        selectedId: {{ $selectedId ?: 'null' }},
                        open: false,
                        get filtered() {
                            const q = this.query.trim().toLowerCase();
                            if (!q) return this.products;
                            return this.products.filter(p => p.name.toLowerCase().includes(q));
                        },
                        pick(p) { this.selectedId = p ? p.id : null; this.query = p ? p.name : ''; this.open = false; },
                    }" @click.outside="open = false">
                    <input type="text" x-model="query" @focus="open = true" @input="open = true; if (!query) pick(null)"
                        placeholder="Search products by name…" autocomplete="off"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="hidden" name="product_id" :value="selectedId">
                    <div x-show="open" x-cloak x-transition
                        class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg py-1">
                        <button type="button" @click="pick(null)" class="w-full text-left px-4 py-2 text-sm text-gray-400 hover:bg-gray-50">— None —</button>
                        <template x-for="p in filtered" :key="p.id">
                            <button type="button" @click="pick(p)"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50"
                                :class="selectedId === p.id ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-700'"
                                x-text="p.name"></button>
                        </template>
                        <p x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">No products match.</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Pulls price/stock from this product unless you set a price override below.</p>
            </div>
        </div>

        {{-- Urgency Bar --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <label class="flex items-center justify-between cursor-pointer">
                <span>
                    <span class="font-medium text-gray-800 block">Urgency Bar</span>
                    <span class="text-xs text-gray-400">Sticky strip above the page with a countdown — "order in the next 10 minutes…"</span>
                </span>
                <input type="checkbox" name="urgency_bar_enabled" value="1" {{ old('urgency_bar_enabled', $lp->urgency_bar_enabled ?? false) ? 'checked' : '' }} class="rounded text-indigo-600 w-5 h-5 shrink-0 ml-3">
            </label>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bar Text</label>
                <input type="text" name="urgency_bar_text" value="{{ old('urgency_bar_text', $lp->urgency_bar_text ?? '') }}" placeholder="১০ মিনিটের মধ্যে অর্ডার করলেই পাবেন ফ্রি ডেলিভারি"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Countdown Length</label>
                <div class="flex items-center gap-2">
                    {{-- min()-clamped for display — a page saved before this min/sec split could
                         still have a stored value like 10 or higher (the old field went up to
                         1440), which would silently violate this input's own max and block the
                         browser from submitting the form at all until re-saved once. --}}
                    <input type="number" min="0" max="9" name="urgency_bar_minutes" value="{{ min(9, (int) old('urgency_bar_minutes', $lp->urgency_bar_minutes ?? 9)) }}"
                        class="w-20 border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-sm text-gray-400">min</span>
                    <input type="number" min="0" max="59" name="urgency_bar_seconds" value="{{ min(59, (int) old('urgency_bar_seconds', $lp->urgency_bar_seconds ?? 45)) }}"
                        class="w-20 border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-sm text-gray-400">sec</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Set any exact time you like — capped under 10 minutes total, to match the bar text's own "within 10 minutes" promise.</p>
            </div>
        </div>

        {{-- Hero --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
            <h3 class="font-medium text-gray-800">Hero</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                    <input type="text" name="hero_heading" value="{{ old('hero_heading', $lp->hero_heading ?? '') }}" placeholder="A headline that grabs attention"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subheading</label>
                    <input type="text" name="hero_subheading" value="{{ old('hero_subheading', $lp->hero_subheading ?? '') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Hero Image</label>
                @if($lp?->hero_image)
                    <img src="{{ Storage::url($lp->hero_image) }}" class="h-20 rounded-lg object-cover mb-2 border border-gray-100">
                @endif
                <input type="file" name="hero_image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rating <span class="text-gray-400 font-normal">(out of 5, optional)</span></label>
                    <input type="number" step="0.1" min="0" max="5" name="rating_value" value="{{ old('rating_value', $lp->rating_value ?? '') }}" placeholder="4.9"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Review Count</label>
                    <input type="number" min="0" name="rating_count" value="{{ old('rating_count', $lp->rating_count ?? '') }}" placeholder="5000"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Trust Badges --}}
        @php
            // :key must be a stable per-row id, not the array index — with an <input
            // type="file"> in the row, removing a middle row and letting Alpine re-key by
            // index would reuse/shift DOM nodes and could leave a file "selected" on the
            // wrong row (browsers won't let JS move a chosen file between inputs to fix
            // that up afterward). Each seeded row gets 'row-N'; add() mints 'new-N'.
            $trustBadgeSeed = collect($seed(['icon' => 'tb_icon', 'text' => 'tb_text', 'image' => 'tb_existing_image'], 'text', collect($lp->trust_badges ?? [])->toArray()))
                ->values()
                ->map(fn ($r, $i) => [
                    'icon'        => $r['icon'] ?? '',
                    'text'        => $r['text'] ?? '',
                    'image'       => $r['image'] ?? '',
                    'imageUrl'    => filled($r['image'] ?? null) ? Storage::url($r['image']) : '',
                    'removeImage' => false,
                    '_key'        => 'row-' . $i,
                ]);
        @endphp
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($trustBadgeSeed) }},
                nextKey: {{ $trustBadgeSeed->count() }},
                add() { this.rows.push({ icon: '✅', text: '', image: '', imageUrl: '', previewUrl: '', removeImage: false, _key: 'new-' + (this.nextKey++) }); },
                remove(i) { this.rows.splice(i, 1); },
                pickFile(row, event) {
                    const f = event.target.files[0];
                    if (!f) return;
                    const reader = new FileReader();
                    reader.onload = e => { row.previewUrl = e.target.result; row.removeImage = false; };
                    reader.readAsDataURL(f);
                },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Trust Badges</h3>
                <p class="text-xs text-gray-400 mt-0.5">Small icon + label strip shown under the hero and again near the price (e.g. "Cash on Delivery", "100% Original", "Fast Delivery"). First 6 shown near the top, first 2 repeated near the price. Each badge can have its own uploaded icon image — pick a different file per row and every one is kept separate; leave any of them blank and that badge just uses its emoji instead.</p>
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="row._key">
                    <div class="border border-gray-100 rounded-xl p-3 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" name="tb_icon[]" x-model="row.icon" placeholder="✅" maxlength="4"
                                class="w-16 border border-gray-200 rounded-lg px-3 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <input type="text" name="tb_text[]" x-model="row.text" placeholder="Cash on Delivery"
                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        {{-- x-show (not x-if) below — the file input must stay in the DOM even
                             once a preview is showing, or the browser drops the file it holds
                             and nothing gets uploaded on submit. Only its visibility toggles. --}}
                        <div class="flex items-center gap-2 pl-0.5">
                            <img x-show="row.previewUrl || (row.image && !row.removeImage)" :src="row.previewUrl || row.imageUrl"
                                class="w-8 h-8 object-contain border border-gray-100 rounded p-0.5 shrink-0">
                            <input type="file" name="tb_image[]" accept="image/*" @change="pickFile(row, $event)"
                                x-show="!(row.previewUrl || (row.image && !row.removeImage))"
                                class="flex-1 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:bg-gray-100 file:text-gray-600 file:cursor-pointer">
                            <button type="button" x-show="row.previewUrl || (row.image && !row.removeImage)"
                                @click="row.previewUrl = ''; row.removeImage = true; $el.parentElement.querySelector('input[type=file]').value = ''"
                                class="text-xs text-red-500 hover:text-red-700 shrink-0">Remove custom icon</button>
                            <span class="text-[11px] text-gray-400" x-show="!(row.previewUrl || (row.image && !row.removeImage))">optional custom icon — falls back to the emoji above</span>
                            <input type="hidden" name="tb_existing_image[]" :value="row.image || ''">
                            <input type="hidden" name="tb_remove_image[]" :value="row.removeImage ? '1' : '0'">
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Badge
            </button>
        </div>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-2">
            <h3 class="font-medium text-gray-800 mb-3">Page Content</h3>
            <p class="text-xs text-gray-400 -mt-2 mb-3">Free-form description shown right after the trust badges — features, story, whatever else sells this product.</p>
            @include('partials.rich-editor', ['name' => 'content', 'value' => old('content', $lp->content ?? ''), 'id' => 'lpcontent', 'placeholder' => 'Write/design the landing page content…'])
        </div>

        {{-- How It Works --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">How It Works Video</h3>
                <p class="text-xs text-gray-400 mt-0.5">Optional explainer video — YouTube, Facebook, or a direct .mp4 link. Leave blank to skip this section entirely.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="text" name="how_it_works_heading" value="{{ old('how_it_works_heading', $lp->how_it_works_heading ?? '') }}" placeholder="Heading (defaults to 'How It Works')"
                    class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <input type="url" name="how_it_works_video" value="{{ old('how_it_works_video', $lp->how_it_works_video ?? '') }}" placeholder="https://youtube.com/watch?v=…"
                    class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Benefits --}}
        @php
            $benefitSeed = collect($seed(['icon' => 'benefit_icon', 'title' => 'benefit_title', 'description' => 'benefit_desc', 'image' => 'benefit_existing_image'], 'title', collect($lp->benefits ?? [])->toArray()))
                ->values()
                ->map(fn ($r, $i) => [
                    'icon'        => $r['icon'] ?? '',
                    'title'       => $r['title'] ?? '',
                    'description' => $r['description'] ?? '',
                    'image'       => $r['image'] ?? '',
                    'imageUrl'    => filled($r['image'] ?? null) ? Storage::url($r['image']) : '',
                    'removeImage' => false,
                    '_key'        => 'row-' . $i,
                ]);
        @endphp
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($benefitSeed) }},
                nextKey: {{ $benefitSeed->count() }},
                add() { this.rows.push({ icon: '✨', title: '', description: '', image: '', imageUrl: '', previewUrl: '', removeImage: false, _key: 'new-' + (this.nextKey++) }); },
                remove(i) { this.rows.splice(i, 1); },
                pickFile(row, event) {
                    const f = event.target.files[0];
                    if (!f) return;
                    const reader = new FileReader();
                    reader.onload = e => { row.previewUrl = e.target.result; row.removeImage = false; };
                    reader.readAsDataURL(f);
                },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Benefits Grid</h3>
                <input type="text" name="benefits_heading" value="{{ old('benefits_heading', $lp->benefits_heading ?? '') }}" placeholder="Section heading (defaults to 'Benefits')"
                    class="w-full mt-2 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Each benefit can have its own uploaded icon image — pick a different file per row and every one is kept separate; leave any of them blank and that benefit just uses its emoji instead.</p>
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="row._key">
                    <div class="border border-gray-100 rounded-xl p-3 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" name="benefit_icon[]" x-model="row.icon" placeholder="✨" maxlength="4"
                                class="w-16 border border-gray-200 rounded-lg px-3 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <input type="text" name="benefit_title[]" x-model="row.title" placeholder="Benefit title"
                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <input type="text" name="benefit_desc[]" x-model="row.description" placeholder="Short description (optional)"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <div class="flex items-center gap-2 pl-0.5">
                            <img x-show="row.previewUrl || (row.image && !row.removeImage)" :src="row.previewUrl || row.imageUrl"
                                class="w-8 h-8 object-contain border border-gray-100 rounded p-0.5 shrink-0">
                            <input type="file" name="benefit_image[]" accept="image/*" @change="pickFile(row, $event)"
                                x-show="!(row.previewUrl || (row.image && !row.removeImage))"
                                class="flex-1 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:bg-gray-100 file:text-gray-600 file:cursor-pointer">
                            <button type="button" x-show="row.previewUrl || (row.image && !row.removeImage)"
                                @click="row.previewUrl = ''; row.removeImage = true; $el.parentElement.querySelector('input[type=file]').value = ''"
                                class="text-xs text-red-500 hover:text-red-700 shrink-0">Remove custom icon</button>
                            <span class="text-[11px] text-gray-400" x-show="!(row.previewUrl || (row.image && !row.removeImage))">optional custom icon — falls back to the emoji above</span>
                            <input type="hidden" name="benefit_existing_image[]" :value="row.image || ''">
                            <input type="hidden" name="benefit_remove_image[]" :value="row.removeImage ? '1' : '0'">
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Benefit
            </button>
        </div>

        {{-- Who Is This For --}}
        @php
            $whoForSeed = collect($seed(['icon' => 'wf_icon', 'text' => 'wf_text', 'image' => 'wf_existing_image'], 'text', collect($lp->who_for ?? [])->toArray()))
                ->values()
                ->map(fn ($r, $i) => [
                    'icon'        => $r['icon'] ?? '',
                    'text'        => $r['text'] ?? '',
                    'image'       => $r['image'] ?? '',
                    'imageUrl'    => filled($r['image'] ?? null) ? Storage::url($r['image']) : '',
                    'removeImage' => false,
                    '_key'        => 'row-' . $i,
                ]);
        @endphp
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($whoForSeed) }},
                nextKey: {{ $whoForSeed->count() }},
                add() { this.rows.push({ icon: '👤', text: '', image: '', imageUrl: '', previewUrl: '', removeImage: false, _key: 'new-' + (this.nextKey++) }); },
                remove(i) { this.rows.splice(i, 1); },
                pickFile(row, event) {
                    const f = event.target.files[0];
                    if (!f) return;
                    const reader = new FileReader();
                    reader.onload = e => { row.previewUrl = e.target.result; row.removeImage = false; };
                    reader.readAsDataURL(f);
                },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Who Is This For</h3>
                <input type="text" name="who_for_heading" value="{{ old('who_for_heading', $lp->who_for_heading ?? '') }}" placeholder="Section heading (defaults to 'Who Is This For')"
                    class="w-full mt-2 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Each row can have its own uploaded icon image — pick a different file per row and every one is kept separate; leave any of them blank and that row just uses its emoji instead.</p>
                <p class="text-xs text-gray-400 mt-1">Select a word and hit <strong>B</strong> (or type <code class="font-mono">**word**</code> yourself) to make it bold on the live page.</p>
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="row._key">
                    <div class="border border-gray-100 rounded-xl p-3 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" name="wf_icon[]" x-model="row.icon" placeholder="👤" maxlength="4"
                                class="w-16 border border-gray-200 rounded-lg px-3 py-2 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <input type="text" name="wf_text[]" x-model="row.text" placeholder="e.g. Anyone managing diabetes"
                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" title="Bold the selected word (or wraps the whole text if nothing's selected)"
                                @click="
                                    const el = $event.target.previousElementSibling;
                                    const s = el.selectionStart ?? row.text.length, e = el.selectionEnd ?? row.text.length;
                                    row.text = row.text.slice(0, s) + '**' + row.text.slice(s, e) + '**' + row.text.slice(e);
                                    $nextTick(() => { el.focus(); el.setSelectionRange(s + 2, e + 2); });
                                "
                                class="w-8 h-8 shrink-0 rounded-lg border border-gray-200 text-xs font-extrabold text-gray-500 hover:text-indigo-600 hover:border-indigo-300 transition">B</button>
                            <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="flex items-center gap-2 pl-0.5">
                            <img x-show="row.previewUrl || (row.image && !row.removeImage)" :src="row.previewUrl || row.imageUrl"
                                class="w-8 h-8 object-contain border border-gray-100 rounded p-0.5 shrink-0">
                            <input type="file" name="wf_image[]" accept="image/*" @change="pickFile(row, $event)"
                                x-show="!(row.previewUrl || (row.image && !row.removeImage))"
                                class="flex-1 text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:bg-gray-100 file:text-gray-600 file:cursor-pointer">
                            <button type="button" x-show="row.previewUrl || (row.image && !row.removeImage)"
                                @click="row.previewUrl = ''; row.removeImage = true; $el.parentElement.querySelector('input[type=file]').value = ''"
                                class="text-xs text-red-500 hover:text-red-700 shrink-0">Remove custom icon</button>
                            <span class="text-[11px] text-gray-400" x-show="!(row.previewUrl || (row.image && !row.removeImage))">optional custom icon — falls back to the emoji above</span>
                            <input type="hidden" name="wf_existing_image[]" :value="row.image || ''">
                            <input type="hidden" name="wf_remove_image[]" :value="row.removeImage ? '1' : '0'">
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Row
            </button>
        </div>

        {{-- Testimonials --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
            <div>
                <h3 class="font-medium text-gray-800">Testimonials</h3>
                <input type="text" name="testimonials_heading" value="{{ old('testimonials_heading', $lp->testimonials_heading ?? '') }}" placeholder="Section heading (defaults to 'What Customers Say')"
                    class="w-full mt-2 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Video testimonials --}}
            <div x-data="{
                    rows: {{ Js::from($seed(['video_url' => 'tv_url', 'name' => 'tv_name'], 'video_url', collect($lp->testimonial_videos ?? [])->toArray())) }},
                    add() { this.rows.push({ video_url: '', name: '' }); },
                    remove(i) { this.rows.splice(i, 1); },
                }" class="space-y-2">
                <p class="text-xs font-medium text-gray-600">Video testimonials <span class="text-gray-400 font-normal">(YouTube / Facebook / .mp4 links)</span></p>
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="url" name="tv_url[]" x-model="row.video_url" placeholder="https://youtube.com/watch?v=…"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="text" name="tv_name[]" x-model="row.name" placeholder="Customer name (optional)"
                            class="w-40 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </template>
                <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Video
                </button>
            </div>

            {{-- Screenshot testimonials --}}
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-medium text-gray-600 mb-2">Screenshot testimonials <span class="text-gray-400 font-normal">(chat/review screenshots)</span></p>
                @include('admin.landing-pages._gallery', ['field' => 'testimonial_images', 'existing' => $lp->testimonial_images ?? []])
            </div>
        </div>

        {{-- Special Offer / Pricing --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($seed(['label' => 'price_label', 'price' => 'price_amount'], 'label', collect($lp->pricing_items ?? [])->toArray())) }},
                add() { this.rows.push({ label: '', price: '' }); },
                remove(i) { this.rows.splice(i, 1); },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Special Offer Box</h3>
                <p class="text-xs text-gray-400 mt-0.5">Itemized lines shown above the final price (e.g. "Product price — ৳600", "Delivery — Free"). The final price itself is the Price Override / linked product price set in the sidebar.</p>
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="text" name="price_label[]" x-model="row.label" placeholder="Product price"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="text" name="price_amount[]" x-model="row.price" placeholder="৳600"
                            class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Line
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Offer Badge Text <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="offer_badge_text" value="{{ old('offer_badge_text', $lp->offer_badge_text ?? '') }}" placeholder="🔥 Special Offer"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Compare-at Price <span class="text-gray-400 font-normal">(struck through)</span></label>
                    <input type="number" step="0.01" min="0" name="compare_at_price" value="{{ old('compare_at_price', $lp->compare_at_price ?? '') }}" placeholder="Leave blank to hide"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Order Form Builder --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                fields: {{ Js::from(old('field_label') ? collect(old('field_label'))->map(fn($l, $i) => [
                    'label' => $l,
                    'type' => old('field_type')[$i] ?? 'text',
                    'required' => (old('field_required')[$i] ?? '0') === '1',
                    'options' => old('field_options')[$i] ?? '',
                ])->values()->all() : collect($existingFields ?? [])->map(fn($f) => [
                    'label' => $f['label'], 'type' => $f['type'],
                    'required' => (bool) ($f['required'] ?? false),
                    'options' => implode(', ', $f['options'] ?? []),
                ])->values()->all()) }},
                addField() { this.fields.push({ label: '', type: 'text', required: false, options: '' }); },
                removeField(i) { this.fields.splice(i, 1); },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Order Form</h3>
                <p class="text-xs text-gray-400 mt-0.5">Name and phone are always collected — required for fulfilling any order. Add extra fields below for anything else you need (size, color, delivery notes, etc.).</p>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="collect_address" value="1" {{ old('collect_address', $lp->collect_address ?? true) ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm text-gray-700">Collect shipping address</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="require_address" value="1" {{ old('require_address', $lp->require_address ?? false) ? 'checked' : '' }} class="rounded text-indigo-600">
                    <span class="text-sm text-gray-700">Make address required</span>
                </label>
            </div>

            <div class="space-y-2">
                <template x-for="(field, i) in fields" :key="i">
                    <div class="flex items-start gap-2 border border-gray-100 rounded-xl p-3">
                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" name="field_label[]" x-model="field.label" placeholder="Field label, e.g. Size"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <select name="field_type[]" x-model="field.type"
                                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="select">Dropdown</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="tel">Phone</option>
                                <option value="email">Email</option>
                                <option value="number">Number</option>
                            </select>
                            <input type="hidden" name="field_required[]" :value="field.required ? '1' : '0'">
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox" x-model="field.required" class="rounded text-indigo-600">
                                Required
                            </label>
                            <input type="text" name="field_options[]" x-model="field.options" x-show="field.type === 'select'"
                                placeholder="Options, comma separated: Small, Medium, Large"
                                class="col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <button type="button" @click="removeField(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove field">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" @click="addField()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Field
            </button>
        </div>

        {{-- Delivery Zones --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($seed(['label' => 'zone_label', 'charge' => 'zone_charge'], 'label', collect($lp->delivery_zones ?? [])->toArray())) }},
                add() { this.rows.push({ label: '', charge: '' }); },
                remove(i) { this.rows.splice(i, 1); },
            }">
            <div>
                <h3 class="font-medium text-gray-800">Delivery Zones</h3>
                <p class="text-xs text-gray-400 mt-0.5">Shipping-charge radio buttons on the order form (e.g. "Inside Dhaka — ৳60", "Outside Dhaka — ৳100"). Leave empty for no shipping charge picker — the order form just collects the address as plain text.</p>
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="text" name="zone_label[]" x-model="row.label" placeholder="Inside Dhaka"
                            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="number" step="0.01" min="0" name="zone_charge[]" x-model="row.charge" placeholder="60"
                            class="w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Zone
            </button>
        </div>

        {{-- FAQ --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4" x-data="{
                rows: {{ Js::from($seed(['question' => 'faq_q', 'answer' => 'faq_a'], 'question', collect($lp->faqs ?? [])->toArray())) }},
                add() { this.rows.push({ question: '', answer: '' }); },
                remove(i) { this.rows.splice(i, 1); },
            }">
            <div>
                <h3 class="font-medium text-gray-800">FAQ</h3>
                <input type="text" name="faqs_heading" value="{{ old('faqs_heading', $lp->faqs_heading ?? '') }}" placeholder="Section heading (defaults to 'FAQ')"
                    class="w-full mt-2 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="space-y-2">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="border border-gray-100 rounded-xl p-3 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="text" name="faq_q[]" x-model="row.question" placeholder="Question"
                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="remove(i)" class="text-red-400 hover:text-red-600 p-2" aria-label="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <textarea name="faq_a[]" x-model="row.answer" rows="2" placeholder="Answer"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add Question
            </button>
        </div>

        {{-- Certificates --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">Certificates / Credentials</h3>
                <p class="text-xs text-gray-400 mt-0.5">ISO certificates, awards, licenses — a photo strip that builds trust.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <input type="text" name="certificates_heading" value="{{ old('certificates_heading', $lp->certificates_heading ?? '') }}" placeholder="Heading (defaults to 'Certified')"
                    class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <input type="text" name="certificates_subheading" value="{{ old('certificates_subheading', $lp->certificates_subheading ?? '') }}" placeholder="Subheading (optional)"
                    class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            @include('admin.landing-pages._gallery', ['field' => 'certificates', 'existing' => $lp->certificates ?? []])
        </div>

        {{-- Thank You Page --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">Thank You Page</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                <input type="text" name="thank_you_heading" value="{{ old('thank_you_heading', $lp->thank_you_heading ?? 'Thank You!') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea name="thank_you_message" rows="2" placeholder="We've received your order and will contact you shortly to confirm."
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('thank_you_message', $lp->thank_you_message ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Redirect URL <span class="text-gray-400 font-normal">(optional — send them somewhere instead, e.g. a Facebook group)</span></label>
                <input type="url" name="thank_you_redirect_url" value="{{ old('thank_you_redirect_url', $lp->thank_you_redirect_url ?? '') }}" placeholder="https://…"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">"Continue Shopping" Button Text <span class="text-gray-400 font-normal">(links to your shop — shown below the thank-you message, hidden if a Redirect URL above sends them elsewhere)</span></label>
                <input type="text" name="thank_you_button_text" value="{{ old('thank_you_button_text', $lp->thank_you_button_text ?? 'আরও প্রোডাক্ট দেখুন') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h3 class="font-medium text-gray-800">SEO</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $lp->meta_title ?? '') }}" placeholder="{{ $lp->title ?? 'Defaults to the page Title above' }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">This is also what shows as the browser tab title — leave blank to just use the page Title above.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                <textarea name="meta_description" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('meta_description', $lp->meta_description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Social Share Image (OG Image)</label>
                @if($lp?->og_image)
                    <img src="{{ Storage::url($lp->og_image) }}" class="h-16 rounded-lg object-cover mb-2 border border-gray-100">
                @endif
                <input type="file" name="og_image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
        </div>

        {{-- Pixel Tracking --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">Pixel Tracking</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Leave any of these blank to use your site-wide default from
                    <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" target="_blank" class="text-indigo-600 hover:underline">Settings → Facebook Pixel</a>
                    /
                    <a href="{{ route('admin.settings.show', 'google_ads') }}" target="_blank" class="text-indigo-600 hover:underline">Google Analytics &amp; Ads</a>
                    instead. Set one here when this specific campaign runs through its own ad account's pixel — e.g. an agency-run page, or a separate product line with its own Meta/Google account.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Facebook / Meta Pixel ID</label>
                <input type="text" name="fb_pixel_id" value="{{ old('fb_pixel_id', $lp->fb_pixel_id ?? '') }}" placeholder="{{ setting('facebook_pixel_id') ?: 'e.g. 123456789012345' }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Meta Events Manager → Data Sources → your pixel. Fires PageView, ViewContent, InitiateCheckout, and Purchase automatically.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GA4 Measurement ID</label>
                    <input type="text" name="ga_measurement_id" value="{{ old('ga_measurement_id', $lp->ga_measurement_id ?? '') }}" placeholder="{{ setting('google_analytics_id') ?: 'G-XXXXXXXXXX' }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Ads Conversion ID</label>
                    <input type="text" name="google_ads_conversion_id" value="{{ old('google_ads_conversion_id', $lp->google_ads_conversion_id ?? '') }}" placeholder="{{ setting('google_ads_conversion_id') ?: 'AW-XXXXXXXXX' }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Google Ads Purchase Conversion Label</label>
                <input type="text" name="google_ads_conversion_label" value="{{ old('google_ads_conversion_label', $lp->google_ads_conversion_label ?? '') }}" placeholder="{{ setting('google_ads_purchase_label') ?: 'AbCdEfGhIj-kLmNoPqR' }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Both from Google Ads → Tools &amp; Settings → Conversions → your Purchase action → "Use Google tag". Needs both fields to fire — a Conversion ID with no label here just skips the Ads conversion tag and only sends the plain GA4 purchase event.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 leading-relaxed">
                📊 Events fired: <span class="font-medium text-gray-700">PageView / ViewContent</span> on page load, <span class="font-medium text-gray-700">InitiateCheckout</span> when the order form is submitted, <span class="font-medium text-gray-700">Purchase</span> once the order goes through — valued at the actual order total, with the customer's name/phone attached for Meta Advanced Matching &amp; Google Enhanced Conversions (same as the main store's checkout).
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4 sticky top-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="draft" {{ old('status', $lp->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft (not public)</option>
                    <option value="published" {{ old('status', $lp->status ?? '') === 'published' ? 'selected' : '' }}>Published (live)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Price Override <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="number" step="0.01" min="0" name="price_override" value="{{ old('price_override', $lp->price_override ?? '') }}" placeholder="Leave blank to use the linked product's price"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">This is the final price shown big in the offer box and order form — set Compare-at Price above to show it struck through against a higher "was" price.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Button Text</label>
                <input type="text" name="order_button_text" value="{{ old('order_button_text', $lp->order_button_text ?? 'Order Now') }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div x-data="{ useCustom: {{ old('brand_color', $lp->brand_color ?? null) ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2 cursor-pointer mb-2">
                    <input type="checkbox" x-model="useCustom" class="rounded text-indigo-600">
                    <span class="text-sm font-medium text-gray-700">Custom Brand Color</span>
                </label>
                <div x-show="useCustom" x-cloak class="flex items-center gap-2">
                    <input type="color" name="brand_color"
                        value="{{ old('brand_color', $lp->brand_color ?? setting('primary_color', '#ea580c')) }}"
                        class="w-12 h-10 border border-gray-200 rounded-lg cursor-pointer shrink-0">
                    <span class="text-xs text-gray-400">Buttons, prices, and borders on this page use this instead of your site's brand color.</span>
                </div>
                <p class="text-xs text-gray-400" x-show="!useCustom">Uses your site's brand color (Settings → Branding) unless you set one here.</p>
                {{-- Unchecking must actually CLEAR a previously-saved color, not just hide the
                     picker — a plain unsubmitted/disabled input wouldn't tell the server "the
                     admin wants this reset," it would just look identical to "field untouched"
                     and leave the old color in place. --}}
                <input type="hidden" name="brand_color_enabled" :value="useCustom ? '1' : '0'">
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                {{ $lp ? 'Save Changes' : 'Create Landing Page' }}
            </button>
            @if($lp)
            <a href="{{ url($lp->slug) }}" target="_blank" class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">View Live Page ↗</a>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Header Logo <span class="text-gray-400 font-normal block text-xs mt-0.5">Optional — falls back to your site logo</span></label>
                @if($lp?->header_logo)
                    <img src="{{ Storage::url($lp->header_logo) }}" class="h-10 object-contain mb-2 border border-gray-100 rounded-lg p-1">
                @endif
                <input type="file" name="header_logo" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
            <div class="pt-4 border-t border-gray-100">
                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon <span class="text-gray-400 font-normal block text-xs mt-0.5">Optional — the small icon next to the browser tab title. Falls back to your site favicon.</span></label>
                @if($lp?->favicon)
                    <img src="{{ Storage::url($lp->favicon) }}" class="h-8 w-8 object-contain mb-2 border border-gray-100 rounded-lg p-1">
                @endif
                <input type="file" name="favicon" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 text-xs text-indigo-700 leading-relaxed">
            💵 All landing page orders are Cash on Delivery — there's no payment method picker on the order form by design, to keep it as short and high-converting as possible.
        </div>
    </div>
</div>
