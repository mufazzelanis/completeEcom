{{-- New / edit topic. $topic (optional), $products (picked rows for edit), $categories, $brands, $blogCategories, $postTypes --}}
@php
    $t = $topic ?? null;
    $tz = \App\Services\AiBlog\Settings::timezone();
@endphp
<form method="POST" action="{{ $t ? route('admin.ai-blog.topics.update', $t) : route('admin.ai-blog.topics.store') }}" class="space-y-4"
      x-data="{
          q: '', results: [], picked: {{ Js::from(old('product_ids') ? [] : ($products ?? [])) }}, loading: false, timer: null,
          search() {
              clearTimeout(this.timer);
              if (this.q.trim().length < 2) { this.results = []; return; }
              this.timer = setTimeout(async () => {
                  this.loading = true;
                  const r = await fetch('{{ route('admin.blog.product-search') }}?q=' + encodeURIComponent(this.q), { headers: { Accept: 'application/json' } });
                  this.results = r.ok ? await r.json() : [];
                  this.loading = false;
              }, 250);
          },
          add(p) { if (!this.picked.find(x => x.id === p.id) && this.picked.length < 8) this.picked.push(p); this.q = ''; this.results = []; },
          remove(id) { this.picked = this.picked.filter(x => x.id !== id); },
      }">
    @csrf
    @if($t) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Topic / working title <span class="text-red-500">*</span></label>
        <input type="text" name="title" required maxlength="255" value="{{ old('title', $t?->title) }}"
            placeholder="e.g. শীতে শুষ্ক ত্বকের জন্য সেরা ময়েশ্চারাইজার"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Article type</label>
            <select name="post_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @foreach($postTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('post_type', $t?->post_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Google keyword <span class="text-gray-400 font-normal">(what people search)</span></label>
            <input type="text" name="keyword" maxlength="255" value="{{ old('keyword', $t?->keyword) }}" placeholder="e.g. dry skin moisturizer bangladesh"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>

    {{-- Products --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Products <span class="text-gray-400 font-normal">(1–8; the article is written from their real details)</span></label>
        <div class="relative">
            <input type="search" x-model="q" @input="search()" placeholder="Search products by name or SKU…"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <div x-show="results.length" x-cloak @click.outside="results = []" class="absolute z-20 mt-1 w-full max-h-72 overflow-y-auto rounded-xl bg-white shadow-lg ring-1 ring-gray-200">
                <template x-for="p in results" :key="p.id">
                    <button type="button" @click="add(p)" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-indigo-50">
                        <img :src="p.image" x-show="p.image" class="w-8 h-8 rounded object-cover" alt="">
                        <span class="flex-1 min-w-0 truncate" x-text="p.name"></span>
                        <span class="text-xs" :class="p.stock > 0 ? 'text-gray-400' : 'text-red-500'" x-text="p.stock > 0 ? '৳' + p.price : 'out of stock'"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            <template x-for="p in picked" :key="p.id">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 text-indigo-700 pl-3 pr-1.5 py-1 text-xs font-medium">
                    <input type="hidden" name="product_ids[]" :value="p.id">
                    <span class="max-w-[16rem] truncate" x-text="p.name"></span>
                    <button type="button" @click="remove(p.id)" class="w-5 h-5 rounded-full hover:bg-indigo-100" aria-label="Remove">×</button>
                </span>
            </template>
        </div>
        <details class="mt-2 text-sm" @if(old('category_id', $t?->category_id) || old('brand_id', $t?->brand_id)) open @endif>
            <summary class="cursor-pointer text-xs text-gray-500">…or let it pick the best sellers of a category / brand</summary>
            <div class="mt-2 grid sm:grid-cols-2 gap-3">
                <select name="category_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">— Category —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $t?->category_id) == $c->id)>{{ $c->parent_id ? '— ' : '' }}{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="brand_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">— Brand —</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(old('brand_id', $t?->brand_id) == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </details>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Mitavin Note <span class="text-gray-400 font-normal">— your own tip / experience (makes the post unique)</span>
        </label>
        <textarea name="note" rows="3" maxlength="3000"
            placeholder="e.g. Customers often ask if it's OK under makeup — yes, wait 2 minutes. Our best seller every winter. Many buy the 50ml first to try."
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('note', $t?->note) }}</textarea>
        <p class="text-xs text-gray-400 mt-1">Without a note the post is always kept as a draft, even in auto-publish mode.</p>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
            <select name="lang" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                <option value="">Default ({{ \App\Models\BlogPost::LANGS[\App\Services\AiBlog\Settings::defaultLang()] }})</option>
                @foreach(\App\Models\BlogPost::LANGS as $code => $label)
                    <option value="{{ $code }}" @selected(old('lang', $t?->lang) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Blog category</label>
            <select name="blog_category_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm">
                <option value="">Default</option>
                @foreach($blogCategories as $c)
                    <option value="{{ $c->id }}" @selected(old('blog_category_id', $t?->blog_category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Not before <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="datetime-local" name="not_before" value="{{ old('not_before', $t?->not_before?->clone()->setTimezone($tz)->format('Y-m-d\TH:i')) }}"
                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm">
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">{{ $t ? 'Save topic' : 'Add to queue' }}</button>
        @if($t)<a href="{{ route('admin.ai-blog.topics') }}" class="text-sm text-gray-500">Cancel</a>@endif
    </div>
</form>
