@extends('layouts.admin')
@section('title', 'Add Product')

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        <span>Back to Products</span>
    </a>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $e)<li class="text-sm text-red-600">{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif
    {{-- Populated client-side for a validation error returned by the AJAX submit (see
         _form.blade.php) — covers any field that isn't one of the few with its own
         inline error-<fieldname> spot next to the input itself. --}}
    <div id="ajax-error-summary" class="hidden bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-sm text-red-600"></div>

    @php $oldSelectedTags = $allTags->whereIn('id', array_map('intval', old('tag_ids', [])))->values(); @endphp
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="product-form"
        x-data="{
            productType: '{{ old('type','simple') }}',
            colors: [],
            sizes: [],
            combinations: [],
            bundleItems: [],
            faqs: [],
            specs: [],
            selectedTags: {{ Js::from($oldSelectedTags) }},
            tagQuery: '',
            showTagDropdown: false,
            creatingTag: false,
            allTags: {{ Js::from($allTags) }},
            allProducts: {{ Js::from($simpleProducts) }},
            attributeNames: {{ Js::from($attributeNames) }},
            get tagSuggestions() {
                if (!this.tagQuery.trim()) return this.allTags.filter(t => !this.selectedTags.find(s=>s.id===t.id));
                const q = this.tagQuery.toLowerCase();
                return this.allTags.filter(t => t.name.toLowerCase().includes(q) && !this.selectedTags.find(s=>s.id===t.id));
            },
            get exactTagMatch() {
                const q = this.tagQuery.trim().toLowerCase();
                return q ? this.allTags.find(t => t.name.toLowerCase() === q) : null;
            },
            addTag(tag) { this.selectedTags.push(tag); this.tagQuery=''; this.showTagDropdown=false; },
            removeTag(id) { this.selectedTags = this.selectedTags.filter(t=>t.id!==id); },
            async createTag() {
                const name = this.tagQuery.trim();
                if (!name || this.creatingTag) return;
                const existing = this.exactTagMatch;
                if (existing) { this.addTag(existing); return; }
                this.creatingTag = true;
                try {
                    const res = await fetch('{{ route('admin.tags.quick-create') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ name }),
                    });
                    if (res.ok) {
                        const tag = await res.json();
                        this.allTags.push(tag);
                        this.addTag(tag);
                    }
                } finally {
                    this.creatingTag = false;
                }
            },
            addColor() { this.colors.push({ id:'', name:'', hex_code:'#6366f1', is_active:true }); this.rebuildCombinations(); },
            removeColor(i) {
                this.colors.splice(i, 1);
                this.combinations = this.combinations
                    .filter(c => c.color_index !== i)
                    .map(c => ({ ...c, color_index: (c.color_index !== null && c.color_index > i) ? c.color_index - 1 : c.color_index }));
                this.rebuildCombinations();
            },
            addSize() { this.sizes.push({ id:'', name:'', is_active:true }); this.rebuildCombinations(); },
            removeSize(i) {
                this.sizes.splice(i, 1);
                this.combinations = this.combinations
                    .filter(c => c.size_index !== i)
                    .map(c => ({ ...c, size_index: (c.size_index !== null && c.size_index > i) ? c.size_index - 1 : c.size_index }));
                this.rebuildCombinations();
            },
            rebuildCombinations() {
                const colorIdxs = this.colors.length ? this.colors.map((_, i) => i) : [null];
                const sizeIdxs = this.sizes.length ? this.sizes.map((_, i) => i) : [null];
                const wanted = [];
                for (const ci of colorIdxs) {
                    for (const si of sizeIdxs) {
                        if (ci === null && si === null) continue;
                        wanted.push(ci + '|' + si);
                    }
                }
                const existingByKey = {};
                this.combinations.forEach(c => { existingByKey[c.color_index + '|' + c.size_index] = c; });
                this.combinations = wanted.map(key => {
                    if (existingByKey[key]) return existingByKey[key];
                    const [ciRaw, siRaw] = key.split('|');
                    return {
                        id: '', color_index: ciRaw === 'null' ? null : parseInt(ciRaw),
                        size_index: siRaw === 'null' ? null : parseInt(siRaw),
                        sku: '', price: '', stock: 0, is_active: true,
                    };
                });
            },
            addBundleItem() { this.bundleItems.push({ product_id:'', quantity:1, discount_pct:0 }); },
            addFaq() { this.faqs.push({ question:'', answer:'' }); },
            addSpec() { this.specs.push({ key:'', value:'' }); },
            specKeyFilter(q) { return this.attributeNames.filter(n => n.toLowerCase().includes(q.toLowerCase())).slice(0,6); },

            // Product Readiness checklist + Live Preview — lifted up here (rather than kept
            // in each field's own local x-data, as these used to be) so one set of fields
            // can be read from several sibling cards at once.
            name: '{{ old('name','') }}',
            shortDescription: {{ Js::from(old('short_description', '')) }},
            descriptionLength: {{ strlen(trim(strip_tags(old('description', '')))) }},
            price: '{{ old('price','') }}',
            salePrice: '{{ old('sale_price','') }}',
            preview: null,
            onFiles(fileList) {
                const file = fileList && fileList[0];
                if (!file || !file.type.startsWith('image/')) return;
                const dt = new DataTransfer(); dt.items.add(file);
                $refs.mainImageInput.files = dt.files;
                const reader = new FileReader();
                reader.onload = e => this.preview = e.target.result;
                reader.readAsDataURL(file);
            },
            clearImage() { this.preview = null; $refs.mainImageInput.value = ''; },
            allSubs: {{ Js::from($allSubcategories) }},
            categoryId: '{{ old('category_id','') }}',
            subcategoryId: '{{ old('subcategory_id','') }}',
            get subcategories() { return this.allSubs[this.categoryId] || [] },
            onCategoryChange() { if (!this.subcategories.find(s => s.id == this.subcategoryId)) this.subcategoryId = ''; },
            brandId: '{{ old('brand_id','') }}',
            allBrands: {{ Js::from($brands) }},
            get brandName() { return this.allBrands.find(b => b.id == this.brandId)?.name || ''; },
            livePreviewOpen: false,
            get completenessChecks() {
                return [
                    { label: 'Product name', ok: this.name.trim().length > 0 },
                    { label: 'Main image', ok: !!this.preview },
                    { label: 'Short description', ok: this.shortDescription.trim().length > 0 },
                    { label: 'Full description', ok: this.descriptionLength > 20 },
                    { label: 'Price', ok: parseFloat(this.price) > 0 },
                    { label: 'Category', ok: this.categoryId !== '' },
                ];
            },
            get completenessPercent() {
                const checks = this.completenessChecks;
                return Math.round((checks.filter(c => c.ok).length / checks.length) * 100);
            },
        }"
        @submit="if (productType === 'variable' && combinations.length === 0) { alert('Add at least one color or size for this variable product.'); $event.preventDefault(); }
                 else if (productType === 'bundle' && bundleItems.length === 0) { alert('Add at least one item to this bundle.'); $event.preventDefault(); }">
        @include('admin.products._form')
    </form>
</div>

@push('scripts')
<script>
    function generateProductSku() {
        const nameInput = document.getElementById('product-name-input');
        const skuInput = document.getElementById('product-sku-input');
        const base = (nameInput.value || 'PROD')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .split('-')
            .filter(Boolean)
            .slice(0, 3)
            .join('-');
        const rand = Math.floor(1000 + Math.random() * 9000);
        skuInput.value = `${base || 'PROD'}-${rand}`;
    }
    function autofillProductSeo() {
        const name = document.getElementById('product-name-input')?.value || '';
        const shortDesc = document.querySelector('[name="short_description"]')?.value || '';
        const metaTitle = document.querySelector('[name="meta_title"]');
        const metaDesc = document.querySelector('[name="meta_description"]');
        if (metaTitle && !metaTitle.value.trim() && name) metaTitle.value = name;
        if (metaDesc && !metaDesc.value.trim() && shortDesc) metaDesc.value = shortDesc;
    }
    // Ctrl/Cmd+S saves the form instead of triggering the browser's "Save Page" dialog —
    // this page has no autosave, so the shortcut a frequent admin reaches for by habit
    // should actually do the useful thing.
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const form = document.getElementById('product-form');
            if (form) { e.preventDefault(); form.requestSubmit(); }
        }
    });
</script>
@endpush
@endsection
