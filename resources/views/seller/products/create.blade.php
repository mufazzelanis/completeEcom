@extends('layouts.seller')
@section('title', 'Add Product')
@section('pageTitle', 'Add Product')

@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-6">Add Product</h1>

<form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data"
    x-data="{
        productType: '{{ old('type', 'simple') }}',
        colors: [],
        sizes: [],
        combinations: [],
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
        }
    }"
    @submit="if (productType === 'variable' && combinations.length === 0) { alert('Add at least one color or size for this variable product.'); $event.preventDefault(); }">
    @include('seller.products._form')
</form>
@endsection
