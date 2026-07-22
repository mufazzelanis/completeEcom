<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductFaq;
use App\Models\ProductImage;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($qr) => $qr
                ->where('name', 'like', "%$q%")
                ->orWhere('sku', 'like', "%$q%")
                ->orWhere('barcode', 'like', "%$q%")
                ->orWhere('short_description', 'like', "%$q%")
            );
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price');
        }
        if ($request->filled('stock_status')) {
            match($request->stock_status) {
                'out' => $query->where('stock', 0),
                'low' => $query->where('stock', '>', 0)->whereRaw('stock <= COALESCE(low_stock_threshold, 5)'),
                'in'  => $query->where('stock', '>', 0),
                default => null,
            };
        }

        $sortBy = $request->get('sort_by', 'latest');
        match($sortBy) {
            'oldest'     => $query->oldest(),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'stock_asc'  => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            default      => $query->latest(),
        };

        $products   = $query->paginate(15)->withQueryString();
        $categories = $this->categoryTree();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    public function create()
    {
        $categoryTree     = $this->categoryTree();
        $allSubcategories = $this->allSubcategoriesJson();
        $brands           = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $allTags          = Tag::orderBy('name')->get(['id', 'name']);
        $attributeNames   = Attribute::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name');
        $simpleProducts   = Product::where('type', '!=', 'bundle')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);

        return view('admin.products.create', compact(
            'categoryTree', 'allSubcategories', 'brands', 'allTags', 'attributeNames', 'simpleProducts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:simple,variable,bundle,digital',
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|numeric|min:0',
            'sale_price'    => 'nullable|numeric|min:0',
            'stock'         => 'nullable|integer|min:0',
            'image'         => 'nullable|image|max:4096',
            'images.*'      => 'nullable|image|max:4096',
            'download_file' => 'nullable|file|max:102400',
        ]);

        $data = $request->only([
            'type', 'name', 'category_id', 'short_description', 'description',
            'sku', 'price', 'weight',
            'meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'robots_meta',
            'sitemap_priority', 'sitemap_changefreq', 'redirect_url', 'breadcrumb_title',
            'og_title', 'og_description', 'og_image',
            'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
            'schema_type', 'schema_condition', 'schema_availability', 'price_valid_until',
            'gtin', 'mpn', 'country_of_origin',
            'google_category', 'google_product_type', 'age_group', 'gender',
            'color_description', 'size_description', 'material',
            'image_alt', 'image_title',
            'ai_summary', 'ai_overview', 'ai_comparison',
        ]);
        $data['noindex']      = $request->boolean('noindex');
        $data['nofollow']     = $request->boolean('nofollow');
        $data['nosnippet']    = $request->boolean('nosnippet');
        $data['noimageindex'] = $request->boolean('noimageindex');
        $data['ai_key_features'] = array_values(array_filter($request->input('ai_key_features', []), fn($v) => trim($v ?? '') !== ''));
        $data['ai_benefits']     = array_values(array_filter($request->input('ai_benefits', []),     fn($v) => trim($v ?? '') !== ''));
        $data['ai_use_cases']    = array_values(array_filter($request->input('ai_use_cases', []),     fn($v) => trim($v ?? '') !== ''));
        $data['seo_score']       = $this->computeSeoScore($data);

        $data['subcategory_id']       = $request->filled('subcategory_id') ? $request->subcategory_id : null;
        $data['brand_id']             = $request->filled('brand_id') ? $request->brand_id : null;
        $data['slug']                 = $this->uniqueSlug(Str::slug($request->name));
        $data['sale_price']           = $request->filled('sale_price') ? $request->sale_price : null;
        $data['stock']                = (int) ($request->stock ?? 0);
        $data['is_active']            = $request->boolean('is_active', true);
        $data['is_featured']          = $request->boolean('is_featured');
        $data['download_expiry_days'] = $request->filled('download_expiry_days') ? (int) $request->download_expiry_days : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product = Product::create($data);

        $this->saveGallery($product, $request);
        $this->syncVariants($product, $request->input('variants', []));
        $this->syncColors($product, $request->input('colors', []), $request);
        $this->syncTags($product, $request->input('tag_ids', []));
        $this->syncFaqs($product, $request->input('faqs', []));
        $this->syncSpecs($product, $request->input('specs', []));
        $this->syncBundleItems($product, $request->input('bundle_items', []));

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $categoryTree     = $this->categoryTree();
        $allSubcategories = $this->allSubcategoriesJson();
        $brands           = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $allTags          = Tag::orderBy('name')->get(['id', 'name']);
        $attributeNames   = Attribute::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name');
        $simpleProducts   = Product::where('type', '!=', 'bundle')->where('is_active', true)->where('id', '!=', $product->id)->orderBy('name')->get(['id', 'name', 'price']);

        $product->load(['images', 'variants', 'colors', 'tags', 'faqs', 'specs', 'bundleItems.itemProduct']);

        return view('admin.products.edit', compact(
            'product', 'categoryTree', 'allSubcategories', 'brands',
            'allTags', 'attributeNames', 'simpleProducts'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:simple,variable,bundle,digital',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'stock'       => 'nullable|integer|min:0',
            'image'       => 'nullable|image|max:4096',
            'images.*'    => 'nullable|image|max:4096',
        ]);

        $data = $request->only([
            'type', 'name', 'category_id', 'short_description', 'description',
            'sku', 'price', 'weight',
            'meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'robots_meta',
            'sitemap_priority', 'sitemap_changefreq', 'redirect_url', 'breadcrumb_title',
            'og_title', 'og_description', 'og_image',
            'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
            'schema_type', 'schema_condition', 'schema_availability', 'price_valid_until',
            'gtin', 'mpn', 'country_of_origin',
            'google_category', 'google_product_type', 'age_group', 'gender',
            'color_description', 'size_description', 'material',
            'image_alt', 'image_title',
            'ai_summary', 'ai_overview', 'ai_comparison',
        ]);
        $data['noindex']      = $request->boolean('noindex');
        $data['nofollow']     = $request->boolean('nofollow');
        $data['nosnippet']    = $request->boolean('nosnippet');
        $data['noimageindex'] = $request->boolean('noimageindex');
        $data['ai_key_features'] = array_values(array_filter($request->input('ai_key_features', []), fn($v) => trim($v ?? '') !== ''));
        $data['ai_benefits']     = array_values(array_filter($request->input('ai_benefits', []),     fn($v) => trim($v ?? '') !== ''));
        $data['ai_use_cases']    = array_values(array_filter($request->input('ai_use_cases', []),     fn($v) => trim($v ?? '') !== ''));
        $data['seo_score']       = $this->computeSeoScore($data);

        $data['subcategory_id']       = $request->filled('subcategory_id') ? $request->subcategory_id : null;
        $data['brand_id']             = $request->filled('brand_id') ? $request->brand_id : null;
        $data['slug']                 = $product->name !== $request->name ? $this->uniqueSlug(Str::slug($request->name), $product->id) : $product->slug;
        $data['sale_price']           = $request->filled('sale_price') ? $request->sale_price : null;
        $data['stock']                = (int) ($request->stock ?? 0);
        $data['is_active']            = $request->boolean('is_active');
        $data['is_featured']          = $request->boolean('is_featured');
        $data['download_expiry_days'] = $request->filled('download_expiry_days') ? (int) $request->download_expiry_days : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product->update($data);

        $this->saveGallery($product, $request);
        if ($request->filled('delete_images')) {
            ProductImage::whereIn('id', $request->delete_images)->where('product_id', $product->id)->delete();
        }
        $this->syncVariants($product, $request->input('variants', []));
        $this->syncColors($product, $request->input('colors', []), $request);
        $this->syncTags($product, $request->input('tag_ids', []));
        $this->syncFaqs($product, $request->input('faqs', []));
        $this->syncSpecs($product, $request->input('specs', []));
        $this->syncBundleItems($product, $request->input('bundle_items', []));

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function categoryTree()
    {
        return Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();
    }

    private function allSubcategoriesJson(): array
    {
        return Category::whereNotNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->groupBy('parent_id')
            ->map(fn($g) => $g->values()->toArray())
            ->toArray();
    }

    private function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $original = $slug;
        $i = 1;
        while (true) {
            $q = Product::where('slug', $slug);
            if ($exceptId) $q->where('id', '!=', $exceptId);
            if (!$q->exists()) break;
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    private function saveGallery(Product $product, Request $request): void
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $img) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image'      => $img->store('products', 'public'),
                    'sort_order' => $product->images()->count() + $i,
                ]);
            }
        }
    }

    private function syncVariants(Product $product, array $rows): void
    {
        $keptIds = [];
        foreach ($rows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) continue;
            $attrs = [
                'name'       => trim($row['name']),
                'sku'        => $row['sku'] ?? null ?: null,
                'price'      => $row['price'] ?? null ?: null,
                'stock'      => (int) ($row['stock'] ?? 0),
                'sort_order' => $i,
                'is_active'  => !empty($row['is_active']),
            ];
            if (!empty($row['id'])) {
                $v = ProductVariant::where('id', $row['id'])->where('product_id', $product->id)->first();
                if ($v) { $v->update($attrs); $keptIds[] = $v->id; continue; }
            }
            $keptIds[] = ProductVariant::create(array_merge($attrs, ['product_id' => $product->id]))->id;
        }
        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncColors(Product $product, array $rows, Request $request): void
    {
        $keptIds = [];
        foreach ($rows as $i => $row) {
            if (empty(trim($row['name'] ?? ''))) continue;
            $attrs = [
                'name'       => trim($row['name']),
                'hex_code'   => $row['hex_code'] ?? null ?: null,
                'stock'      => $row['stock'] ?? null ?: null,
                'sort_order' => $i,
                'is_active'  => !empty($row['is_active']),
            ];
            if ($request->hasFile("color_images.{$i}")) {
                $attrs['image'] = $request->file("color_images.{$i}")->store('products/colors', 'public');
            }
            if (!empty($row['id'])) {
                $c = ProductColor::where('id', $row['id'])->where('product_id', $product->id)->first();
                if ($c) {
                    if (empty($attrs['image'])) unset($attrs['image']);
                    $c->update($attrs); $keptIds[] = $c->id; continue;
                }
            }
            $keptIds[] = ProductColor::create(array_merge($attrs, ['product_id' => $product->id]))->id;
        }
        $product->colors()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync(array_filter(array_map('intval', $tagIds)));
    }

    private function syncFaqs(Product $product, array $rows): void
    {
        $product->faqs()->delete();
        foreach ($rows as $i => $row) {
            if (empty(trim($row['question'] ?? '')) || empty(trim($row['answer'] ?? ''))) continue;
            ProductFaq::create([
                'product_id' => $product->id,
                'question'   => trim($row['question']),
                'answer'     => trim($row['answer']),
                'sort_order' => $i,
            ]);
        }
    }

    private function syncSpecs(Product $product, array $rows): void
    {
        $product->specs()->delete();
        foreach ($rows as $i => $row) {
            if (empty(trim($row['key'] ?? '')) || empty(trim($row['value'] ?? ''))) continue;
            ProductSpec::create([
                'product_id' => $product->id,
                'spec_key'   => trim($row['key']),
                'spec_value' => trim($row['value']),
                'sort_order' => $i,
            ]);
        }
    }

    private function syncBundleItems(Product $product, array $rows): void
    {
        $product->bundleItems()->delete();

        if (!$product->isBundle()) {
            return;
        }

        $validItemIds = Product::where('type', '!=', 'bundle')
            ->where('id', '!=', $product->id)
            ->pluck('id')
            ->flip();

        foreach ($rows as $i => $row) {
            $itemId = (int) ($row['product_id'] ?? 0);
            if (!$itemId || !isset($validItemIds[$itemId])) continue;

            BundleItem::create([
                'bundle_product_id' => $product->id,
                'item_product_id'   => $itemId,
                'quantity'          => max(1, (int) ($row['quantity'] ?? 1)),
                'discount_pct'      => min(100, max(0, (float) ($row['discount_pct'] ?? 0))),
                'sort_order'        => $i,
            ]);
        }
    }

    private function computeSeoScore(array $data): int
    {
        $score = 0;
        $mt  = trim($data['meta_title']    ?? '');
        $md  = trim($data['meta_description'] ?? '');
        $fk  = trim($data['focus_keyword'] ?? '');
        $ogi = trim($data['og_image']      ?? '');
        $alt = trim($data['image_alt']     ?? '');
        $des = trim($data['description']   ?? '');

        if (strlen($mt) >= 30 && strlen($mt) <= 70) $score += 20; elseif ($mt !== '') $score += 5;
        if (strlen($md) >= 100 && strlen($md) <= 160) $score += 20; elseif ($md !== '') $score += 5;
        if ($fk !== '') $score += 15;
        if ($ogi !== '') $score += 10;
        if ($alt !== '') $score += 10;
        if (strlen($des) > 200) $score += 15; elseif ($des !== '') $score += 5;
        if ($fk !== '' && $mt !== '' && str_contains(strtolower($mt), strtolower($fk))) $score += 10;

        return min($score, 100);
    }
}
