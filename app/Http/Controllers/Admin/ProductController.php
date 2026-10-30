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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use SyncsProductVariants;

    /**
     * The filter half of index() — pulled out so index() (paginated list) and
     * ids() ("select all N matching" for bulk actions) always agree on exactly
     * which products a given set of query-string filters matches. Sorting isn't
     * included here since ids() doesn't care about order, only membership.
     */
    private function filteredProducts(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $q = trim($request->search);
            // Every word must appear somewhere in the name/SKU, in any order — a single
            // "%whole phrase%" match missed near-identical products whose names differ by one
            // word or spelling ("Cream Normal To Dry" vs "Cream For Normal To Dry"), so a
            // search for one never showed its duplicate. A number (or #number) also matches
            // the product ID.
            $words = array_filter(array_map(fn ($w) => trim($w, " \t-–—()[],."), preg_split('/\s+/u', $q)), 'strlen');
            $query->where(function ($qr) use ($q, $words) {
                $qr->where(function ($all) use ($words) {
                    foreach ($words as $word) {
                        $all->where(fn ($w) => $w->where('name', 'like', "%{$word}%")->orWhere('sku', 'like', "%{$word}%"));
                    }
                })
                    ->orWhere('sku', 'like', "%$q%")
                    ->orWhere('barcode', 'like', "%$q%")
                    ->orWhere('short_description', 'like', "%$q%");
                if (ctype_digit(ltrim($q, '#'))) {
                    $qr->orWhere('id', (int) ltrim($q, '#'));
                }
            });
        }
        if ($request->filled('category')) {
            // a sub-category in the filter matches products filed under it too
            $query->where(fn ($q) => $q->where('category_id', $request->category)->orWhere('subcategory_id', $request->category));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('brand')) {
            $request->brand === 'none' ? $query->whereNull('brand_id') : $query->where('brand_id', $request->brand);
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
                'in'  => $query->where('stock', '>', 0)->where('marked_out_of_stock', false),
                'marked' => $query->where('marked_out_of_stock', true), // switched off by hand, stock untouched
                default => null,
            };
        }

        return $query;
    }

    /** Session key for the Products list's last filters/page (see index()). */
    private const FILTER_MEMORY = 'admin.products.filters';

    public function index(Request $request)
    {
        // Sticky filters: the list comes back exactly as it was left (filters, sort, page) after
        // editing, deleting, duplicating or a bulk action — or coming back from the sidebar —
        // until "Clear" (?reset=1) forgets them.
        if ($request->boolean('reset')) {
            session()->forget(self::FILTER_MEMORY);

            return redirect()->route('admin.products.index');
        }
        $params = array_filter($request->query(), fn ($v) => $v !== null && $v !== '');
        if ($params) {
            session([self::FILTER_MEMORY => $params]);
        } elseif ($saved = session(self::FILTER_MEMORY)) {
            return redirect()->route('admin.products.index', $saved);
        }

        $query = $this->filteredProducts($request)->with(['category', 'brand', 'seller', 'subcategory']);

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

        // Drag-to-reorder only makes sense against one category's full list at once
        // — sort_order is what ShopController::category() sorts by on the live
        // category page, so this is literally "which product shows first" there.
        // Reordering across a paginated slice would silently corrupt sort_order for
        // whatever's on other pages, so this mode shows the whole category on one
        // "page" instead of the usual 15.
        $isManualReorder = $sortBy === 'manual' && $request->filled('category');
        $reorderCategoryName = $isManualReorder ? Category::find($request->category)?->name : null;

        $products   = $query->paginate($isManualReorder ? 1000 : 15)->withQueryString();
        $categories = $this->categoryTree();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $vendors    = Vendor::approved()->orderBy('business_name')->get(['id', 'business_name']);
        $pendingApprovalCount = Product::where('approval_status', 'pending')->count();

        return view('admin.products.index', compact(
            'products', 'categories', 'brands', 'vendors', 'pendingApprovalCount',
            'isManualReorder', 'reorderCategoryName'
        ));
    }

    /**
     * Products that can go into a bundle (simple/digital, active) for the bundle-item picker on
     * the create/edit form — fetched by that form only when the product is a bundle, instead
     * of being embedded in every product form. ?except= leaves out the bundle itself.
     */
    public function bundleOptions(Request $request)
    {
        return response()->json(
            Product::whereNotIn('type', ['bundle', 'variable'])->where('is_active', true)
                ->when($request->integer('except'), fn ($q, $id) => $q->whereKeyNot($id))
                ->orderBy('name')->get(['id', 'name', 'price'])
        );
    }

    /**
     * Every product id matching the current filters, regardless of page — backs
     * the "Select all N matching products" bulk-select link, so it can select
     * products beyond the 15 shown on the current page.
     */
    public function ids(Request $request)
    {
        return response()->json([
            'ids' => $this->filteredProducts($request)->pluck('id'),
        ]);
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

        return view('admin.products.create', compact(
            'categoryTree', 'allSubcategories', 'brands', 'allTags', 'attributeNames'
        ));
    }

    /**
     * $request->validate() on its own only renders as JSON on failure when Laravel's
     * own expectsJson() heuristic agrees — which in practice didn't trigger reliably for
     * the product form's plain fetch() (Accept: application/json, no X-Requested-With),
     * falling back to its normal redirect()->back() behavior instead. Explicit here
     * removes that guesswork: wantsJson() (just the Accept header) decides, not a mix of
     * signals. Returns the 422 JSON response to return early with, or null to continue.
     */
    private function validateProduct(Request $request, array $rules): ?JsonResponse
    {
        try {
            $request->validate($rules);
            return null;
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }
            throw $e;
        }
    }

    public function store(Request $request)
    {
        if ($response = $this->validateProduct($request, [
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:simple,variable,bundle,digital',
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|numeric|min:0',
            'sale_price'    => 'nullable|numeric|min:0',
            'stock'         => 'nullable|integer|min:0',
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'sort_order'    => 'nullable|integer|min:0',
            'image'         => 'nullable|image|max:4096',
            'images.*'      => 'nullable|image|max:4096',
            // A digital product with no file has nothing to deliver — require it
            // up front rather than letting it silently publish empty.
            'download_file' => $request->input('type') === 'digital' ? 'required|file|max:102400' : 'nullable|file|max:102400',
        ])) {
            return $response;
        }

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
        $data['sort_order']           = $request->filled('sort_order') ? (int) $request->sort_order : 0;
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

        // The product form submits via fetch (see admin/products/_form.blade.php) to avoid
        // the full-page-reload feel of a normal redirect — flash the message too, so it's
        // there as usual once the browser's own Turbo.visit(redirect) below lands on the
        // index (a plain history replace, like the update() branch does, would leave this
        // page showing stale "Create" fields for a product that already exists).
        if ($request->wantsJson()) {
            session()->flash('success', 'Product created successfully.');
            return response()->json(['message' => 'Product created successfully.', 'redirect' => route('admin.products.index')]);
        }

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

        $product->load(['images', 'colors', 'sizes', 'combinations.color', 'combinations.size', 'tags', 'faqs', 'specs', 'bundleItems.itemProduct']);
        $variantMatrix = $this->variantMatrixJsData($product);

        return view('admin.products.edit', compact(
            'product', 'categoryTree', 'allSubcategories', 'brands',
            'allTags', 'attributeNames', 'variantMatrix'
        ));
    }

    public function update(Request $request, Product $product)
    {
        if ($response = $this->validateProduct($request, [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:simple,variable,bundle,digital',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'stock'       => 'nullable|integer|min:0',
            'sku'         => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'sort_order'  => 'nullable|integer|min:0',
            'image'       => 'nullable|image|max:4096',
            'images.*'    => 'nullable|image|max:4096',
            // Only required if there isn't already a file saved — editing other
            // fields shouldn't force a re-upload.
            'download_file' => ($request->input('type') === 'digital' && ! $product->download_file)
                ? 'required|file|max:102400' : 'nullable|file|max:102400',
        ])) {
            return $response;
        }

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
        $data['sort_order']           = $request->filled('sort_order') ? (int) $request->sort_order : $product->sort_order;
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

        // Stays on the same page (see admin/products/_form.blade.php) — no redirect is
        // actually followed, just a toast; "redirect" here is only the fresh edit URL in
        // case the slug changed (the name was edited), so the browser's address bar can be
        // silently kept correct via history.replaceState without a real navigation.
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Product updated successfully.', 'redirect' => route('admin.products.edit', $product)]);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        if ($request->wantsJson()) {
            $deleted = $this->deleteOrArchive($product);

            return response()->json(['message' => $deleted
                ? "\"{$product->name}\" deleted."
                : "\"{$product->name}\" couldn't be deleted because other records still depend on it, so it was deactivated instead."]);
        }

        if ($this->deleteOrArchive($product)) {
            return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
        }

        return redirect()->route('admin.products.index')->with('error',
            "\"{$product->name}\" couldn't be deleted because other records still depend on it, so it was deactivated instead.");
    }

    /**
     * Deletes the product for real. Its stock-adjustment and purchase history survive on
     * their own (they keep a copy of the product's name/SKU and their product_id is set to
     * NULL — see the keep_inventory_ledger_when_products_are_deleted migration); orders keep
     * their line items the same way. Only if some *other* table ever adds a RESTRICT key on
     * products does this fall back to deactivating it, instead of the bare 500 page the
     * admin used to get. Returns true if deleted.
     */
    private function deleteOrArchive(Product $product): bool
    {
        try {
            $product->delete();

            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) {
                throw $e;
            }
        }

        $product->update(['is_active' => false, 'is_featured' => false]);

        return false;
    }

    /**
     * "Quick entry" for this catalog's actual pattern: many products are near-identical
     * variants of each other (same item, different size/ml — see the CeraVe/Aveeno/Boots
     * lines), so starting a new one from an existing listing beats retyping everything.
     * Copies the core fields, specs, FAQs, image and gallery; deliberately leaves out
     * variant colors/sizes/combinations and bundle items (the two product types this
     * catalog doesn't actually use for its near-duplicate items) to keep this predictable.
     * Starts inactive so it never goes live unedited — the admin lands straight on its
     * Edit page to adjust name/price/images before publishing.
     */
    public function duplicate(Product $product)
    {
        $copy = $product->replicate(['slug', 'sku', 'views', 'approval_status', 'rejection_reason']);
        $copy->name = $product->name . ' (Copy)';
        $copy->slug = $this->uniqueSlug(Str::slug($copy->name));
        $copy->sku = null;
        $copy->views = 0;
        $copy->is_active = false;
        $copy->approval_status = 'approved';
        $copy->save();

        foreach ($product->specs as $spec) {
            $copy->specs()->create(['spec_key' => $spec->spec_key, 'spec_value' => $spec->spec_value, 'sort_order' => $spec->sort_order]);
        }
        foreach ($product->faqs as $faq) {
            $copy->faqs()->create(['question' => $faq->question, 'answer' => $faq->answer, 'sort_order' => $faq->sort_order]);
        }
        foreach ($product->images as $image) {
            $copy->images()->create(['image' => $image->image, 'sort_order' => $image->sort_order]);
        }
        $copy->tags()->sync($product->tags->pluck('id'));

        return redirect()->route('admin.products.edit', $copy)
            ->with('success', 'Duplicated as "' . $copy->name . '" — review and publish when ready.');
    }

    /**
     * One request for every "select several rows, do X" action on the product list —
     * status/featured toggles are a single UPDATE across the whole id set (no per-row
     * Eloquent event to preserve here, same as the single-product update() calls
     * elsewhere in this controller), delete reuses the same product_id ON DELETE SET
     * NULL the single-product destroy() already relies on, so old orders keep their
     * line items even once the product itself is gone.
     */
    /**
     * The Products list's "Available / Out of stock" switch: shows the product as out of stock
     * to customers (and blocks ordering it) without touching the real stock count. Turning it
     * back on makes the same stock sellable again — and "Notify me" shoppers get their message.
     */
    public function toggleStockSwitch(Request $request, Product $product)
    {
        $out = $request->has('out') ? $request->boolean('out') : !$product->marked_out_of_stock;
        $product->forceFill(['marked_out_of_stock' => $out])->save();
        \App\Services\AuditLogger::log('product.stock_switch', ($out ? 'Marked out of stock: ' : 'Marked available again: ') . $product->name, $product);

        return response()->json([
            'out' => $out,
            'message' => $out ? "\"{$product->name}\" now shows as Out of stock." : "\"{$product->name}\" is available again ({$product->stock} in stock).",
        ]);
    }

    /**
     * Quick edit from the Products list (the row's ⚡ panel): name, category, brand, prices,
     * stock and the switches, saved without leaving the list. Only the fields sent are
     * touched. Stock changes go through the same ledger as Stock Management's quick update.
     * Returns the re-rendered row so the list updates in place — filters, page and scroll
     * position stay exactly where they were.
     */
    public function quickUpdate(Request $request, Product $product)
    {
        // JSON-only endpoint; errors go back as 422 field errors for the panel
        // (bootstrap/app.php renders JSON automatically only for api/*)
        try {
            $v = $this->validateQuickUpdate($request, $product);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        $before = (int) $product->stock;
        $stock = $v['stock'] ?? null;
        unset($v['stock']);
        if (isset($v['name']) && $v['name'] !== $product->name) {
            $v['slug'] = $this->uniqueSlug(Str::slug($v['name']), $product->id);
        }

        DB::transaction(function () use ($product, $v, $stock, $before) {
            $product->fill($v);
            if ($stock !== null && (int) $stock !== $before) {
                $product->stock = (int) $stock;
                \App\Models\StockAdjustment::create([
                    'product_id' => $product->id,
                    'type' => $stock > $before ? 'manual_in' : 'manual_out',
                    'quantity' => abs($stock - $before),
                    'stock_before' => $before,
                    'stock_after' => (int) $stock,
                    'reference' => 'QUICK-' . date('Ymd'),
                    'reason' => 'Quick edit (Products list)',
                    'adjusted_by' => auth()->id(),
                ]);
            }
            $changed = array_keys($product->getDirty());
            $product->save();
            if ($changed) {
                \App\Services\AuditLogger::log('product.quick_edit', 'Quick edit: ' . $product->name . ' (' . implode(', ', $changed) . ')', $product);
            }
        });

        return response()->json([
            'message' => "\"{$product->name}\" saved.",
            'row' => $this->renderRow($product),
        ]);
    }

    private function validateQuickUpdate(Request $request, Product $product): array
    {
        $v = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'subcategory_id' => 'sometimes|nullable|exists:categories,id',
            'brand_id' => 'sometimes|nullable|exists:brands,id',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'marked_out_of_stock' => 'sometimes|boolean',
        ]);

        // a lower regular price is checked against the sale price already saved, too
        $price = (float) ($v['price'] ?? $product->price);
        $sale = array_key_exists('sale_price', $v) ? $v['sale_price'] : $product->sale_price;
        if ($sale !== null && (float) $sale >= $price) {
            throw ValidationException::withMessages([array_key_exists('sale_price', $v) ? 'sale_price' : 'price' => 'Sale price (৳' . number_format((float) $sale) . ') must be lower than the regular price (৳' . number_format($price) . ').']);
        }
        if (array_key_exists('subcategory_id', $v) && $v['subcategory_id']) {
            $parent = $v['category_id'] ?? $product->category_id;
            if ((int) Category::whereKey($v['subcategory_id'])->value('parent_id') !== (int) $parent) {
                throw ValidationException::withMessages(['subcategory_id' => 'That sub-category belongs to a different category.']);
            }
        }
        // a new category without a sub-category clears the old one (it belonged to the old category)
        if (isset($v['category_id']) && (int) $v['category_id'] !== (int) $product->category_id && !array_key_exists('subcategory_id', $v)) {
            $v['subcategory_id'] = null;
        }
        // variable products keep stock per colour/size (products.stock mirrors those)
        if ($product->type === 'variable') {
            unset($v['stock']);
        }

        return $v;
    }

    /** One list row, as index() draws it — for in-place updates. */
    private function renderRow(Product $product): string
    {
        $product->refresh()->load(['category', 'brand', 'seller', 'subcategory']);

        return view('admin.products._row', [
            'product' => $product,
            'categories' => $this->categoryTree(),
            'isManualReorder' => false,
        ])->render();
    }

    public function bulkAction(Request $request)
    {
        $rules = [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
            'bulk_action' => 'required|in:activate,deactivate,feature,unfeature,mark_out,mark_available,set_category,set_brand,delete',
            'category_id' => 'required_if:bulk_action,set_category|nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
        ];
        if ($response = $this->validateProduct($request, $rules)) {
            return $response;   // 422 JSON for the list's fetch
        }
        $validated = $request->validate($rules);

        $query = Product::whereIn('id', $validated['ids']);
        $count = count($validated['ids']);

        // Category / brand for many at once — one by one so model events (cache refresh) fire.
        // A sub-category picked here files the product under its parent + that sub-category.
        if ($validated['bulk_action'] === 'set_category') {
            $cat = Category::find($validated['category_id']);
            $fields = $cat->parent_id
                ? ['category_id' => $cat->parent_id, 'subcategory_id' => $cat->id]
                : ['category_id' => $cat->id, 'subcategory_id' => null];
            $query->get()->each(fn ($p) => $p->forceFill($fields)->save());
            $label = $cat->name;
        }
        if ($validated['bulk_action'] === 'set_brand') {
            $brandId = $validated['brand_id'] ?? null;
            $query->get()->each(fn ($p) => $p->forceFill(['brand_id' => $brandId])->save());
            $label = $brandId ? Brand::whereKey($brandId)->value('name') : null;
        }

        // Delete goes product by product (not one bulk query) so a single product with
        // inventory history no longer fails the whole batch with a 500 — it's archived
        // instead (see deleteOrArchive) — and model events (cache refresh) still fire.
        $archived = 0;
        if ($validated['bulk_action'] === 'delete') {
            foreach ($query->get() as $product) {
                $archived += $this->deleteOrArchive($product) ? 0 : 1;
            }
        }

        match ($validated['bulk_action']) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'feature' => $query->update(['is_featured' => true]),
            'unfeature' => $query->update(['is_featured' => false]),
            // one by one so model events fire (homepage/shop caches refresh right away)
            'mark_out', 'mark_available' => $query->get()->each(fn ($p) => $p->forceFill(['marked_out_of_stock' => $validated['bulk_action'] === 'mark_out'])->save()),
            'set_category', 'set_brand', 'delete' => null,
        };

        $messages = [
            'set_category' => "$count product(s) moved to " . ($label ?? '') . '.',
            'set_brand' => ($label ?? null) ? "Brand set to $label for $count product(s)." : "Brand removed from $count product(s).",
            'activate' => "$count product(s) activated.",
            'deactivate' => "$count product(s) deactivated.",
            'feature' => "$count product(s) marked as Featured.",
            'unfeature' => "$count product(s) unmarked as Featured.",
            'mark_out' => "$count product(s) now show as Out of stock.",
            'mark_available' => "$count product(s) are available again.",
            'delete' => ($count - $archived) . ' product(s) deleted.'
                . ($archived ? " $archived couldn't be deleted (other records depend on them), so they were deactivated instead." : ''),
        ];

        if ($request->wantsJson()) {
            return response()->json(['message' => $messages[$validated['bulk_action']]]);
        }

        // index() restores the remembered filters/page
        return redirect()->route('admin.products.index')->with('success', $messages[$validated['bulk_action']]);
    }

    public function moveUp(Product $product)
    {
        $this->swapWithNeighbor($product, 'up');
        return back();
    }

    /**
     * Saves a fresh drag-and-drop order from the admin Products list's manual
     * reorder mode (see index()'s $isManualReorder) — the array's position becomes
     * each product's new sort_order, exactly like dropping cards in a stack.
     * Persisted with one query (a single CASE-per-id UPDATE) rather than one query
     * per product, since a full category can be dozens of rows and this fires on
     * every drop, not just on save.
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        // Safe to interpolate: every id has already passed the 'integer' rule above
        // and is re-cast here, so this can only ever contain plain integers.
        $ids = array_map('intval', $validated['ids']);
        $cases = collect($ids)->map(fn ($id, $i) => "WHEN $id THEN $i")->implode(' ');

        DB::table('products')
            ->whereIn('id', $ids)
            ->update(['sort_order' => DB::raw("CASE id $cases ELSE sort_order END")]);

        return response()->json(['success' => true]);
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
