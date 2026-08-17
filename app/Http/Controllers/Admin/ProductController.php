<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SyncsProductVariants;
use App\Http\Controllers\Controller;
use App\Support\ImageOptimizer;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\BundleItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductImage;
use App\Models\ProductSpec;
use App\Models\Tag;
use App\Models\Vendor;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use SyncsProductVariants;

    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'seller']);

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
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        if ($request->filled('seller')) {
            $query->where('seller_id', $request->seller);
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
            'manual'     => $query->orderBy('sort_order')->orderBy('name'),
            default      => $query->latest(),
        };

        $products   = $query->paginate(15)->withQueryString();
        $categories = $this->categoryTree();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $vendors    = Vendor::approved()->orderBy('business_name')->get(['id', 'business_name']);
        $pendingApprovalCount = Product::where('approval_status', 'pending')->count();

        return view('admin.products.index', compact('products', 'categories', 'brands', 'vendors', 'pendingApprovalCount'));
    }

    public function approve(Request $request, Product $product)
    {
        $request->validate(['category_id' => 'nullable|exists:categories,id']);

        $data = ['approval_status' => 'approved', 'is_active' => true, 'rejection_reason' => null];
        if ($request->filled('category_id')) {
            $data['category_id'] = $request->category_id;
        }
        $product->update($data);

        if ($product->seller) {
            NotificationDispatcher::customer('vendor_product_approved', $product->seller->user, [
                'product_name' => $product->name,
                'url' => route('products.show', $product),
            ]);
        }

        return back()->with('success', 'Product approved and is now live.');
    }

    public function reject(Request $request, Product $product)
    {
        $request->validate(['rejection_reason' => 'required|string|max:255']);

        $product->update([
            'approval_status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $request->rejection_reason,
        ]);

        if ($product->seller) {
            NotificationDispatcher::customer('vendor_product_rejected', $product->seller->user, [
                'product_name' => $product->name,
                'reason' => $request->rejection_reason,
            ]);
        }

        return back()->with('success', 'Product rejected.');
    }

    public function create()
    {
        $categoryTree     = $this->categoryTree();
        $allSubcategories = $this->allSubcategoriesJson();
        $brands           = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $allTags          = Tag::orderBy('name')->get(['id', 'name']);
        $attributeNames   = Attribute::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name');
        // 'variable' excluded too: bundling one would decrement its products.stock
        // directly on checkout, desyncing it from the per-combination stock that
        // column is supposed to mirror — there's no "which color/size" UI here.
        $simpleProducts   = Product::whereNotIn('type', ['bundle', 'variable'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);

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
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'image'         => 'nullable|image|max:4096',
            'images.*'      => 'nullable|image|max:4096',
            // A digital product with no file has nothing to deliver — require it
            // up front rather than letting it silently publish empty.
            'download_file' => $request->input('type') === 'digital' ? 'required|file|max:102400' : 'nullable|file|max:102400',
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
            $data['image'] = ImageOptimizer::store($request->file('image'), 'products', 'public', 1200);
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product = Product::create($data);

        $this->saveGallery($product, $request);
        $colorIdsByIndex = $this->syncProductColors($product, $request->input('colors', []), $request);
        $sizeIdsByIndex = $this->syncProductSizes($product, $request->input('sizes', []));
        $this->syncProductCombinations($product, $request->input('combinations', []), $colorIdsByIndex, $sizeIdsByIndex);
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
        $simpleProducts   = Product::whereNotIn('type', ['bundle', 'variable'])->where('is_active', true)->where('id', '!=', $product->id)->orderBy('name')->get(['id', 'name', 'price']);

        $product->load(['images', 'colors', 'sizes', 'combinations.color', 'combinations.size', 'tags', 'faqs', 'specs', 'bundleItems.itemProduct']);
        $variantMatrix = $this->variantMatrixJsData($product);

        return view('admin.products.edit', compact(
            'product', 'categoryTree', 'allSubcategories', 'brands',
            'allTags', 'attributeNames', 'simpleProducts', 'variantMatrix'
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
            'sku'         => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'image'       => 'nullable|image|max:4096',
            'images.*'    => 'nullable|image|max:4096',
            // Only required if there isn't already a file saved — editing other
            // fields shouldn't force a re-upload.
            'download_file' => ($request->input('type') === 'digital' && ! $product->download_file)
                ? 'required|file|max:102400' : 'nullable|file|max:102400',
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
            $data['image'] = ImageOptimizer::store($request->file('image'), 'products', 'public', 1200);
        }
        if ($request->hasFile('download_file')) {
            $data['download_file'] = $request->file('download_file')->store('downloads', 'private');
        }

        $product->update($data);

        if ($request->filled('delete_images')) {
            ProductImage::whereIn('id', $request->delete_images)->where('product_id', $product->id)->delete();
        }
        if ($request->filled('existing_image_order')) {
            $this->reorderGallery($product, $request->input('existing_image_order'));
        }
        $this->saveGallery($product, $request);
        $colorIdsByIndex = $this->syncProductColors($product, $request->input('colors', []), $request);
        $sizeIdsByIndex = $this->syncProductSizes($product, $request->input('sizes', []));
        $this->syncProductCombinations($product, $request->input('combinations', []), $colorIdsByIndex, $sizeIdsByIndex);
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

    public function moveUp(Product $product)
    {
        $this->swapWithNeighbor($product, 'up');
        return back();
    }

    public function moveDown(Product $product)
    {
        $this->swapWithNeighbor($product, 'down');
        return back();
    }

    /**
     * Scoped by category_id, same as Category::swapWithNeighbor is scoped by
     * parent_id — manual order only makes sense among products the admin is
     * already viewing together (one category's product list), not globally
     * across unrelated categories.
     */
    private function swapWithNeighbor(Product $product, string $direction): void
    {
        $siblings = Product::where('category_id', $product->category_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $index = $siblings->search(fn ($s) => $s->id === $product->id);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $siblings->count()) {
            return;
        }

        $neighbor = $siblings[$swapIndex];

        $product->update(['sort_order' => $swapIndex]);
        $neighbor->update(['sort_order' => $index]);
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
                    'image'      => ImageOptimizer::store($img, 'products', 'public', 1200),
                    'sort_order' => $product->images()->count() + $i,
                ]);
            }
        }
    }

    private function reorderGallery(Product $product, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $i => $id) {
            ProductImage::where('id', $id)->where('product_id', $product->id)->update(['sort_order' => $i]);
        }
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

        $validItemIds = Product::whereNotIn('type', ['bundle', 'variable'])
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

        $product->refreshBundleStock();
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
