<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Tag;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        // A bare ?category=<one slug> or ?brand=<slug> (nothing else but ?page) duplicates
        // that category/brand's own page — 301 there so old links and Google consolidate
        // onto the one indexable URL.
        $filters = collect($request->except('page'))->filter(fn ($v) => $v !== null && $v !== '');
        if ($filters->keys()->all() === ['category'] && !str_contains($filters['category'], ',')
            && ($cat = Category::active()->where('slug', $filters['category'])->first())) {
            return redirect()->route('shop.category', array_filter([$cat, 'page' => $request->page]), 301);
        }
        if ($filters->keys()->all() === ['brand']
            && ($brandModel = Brand::where('is_active', true)->where('slug', $filters['brand'])->first())) {
            return redirect()->route('shop.brand', array_filter([$brandModel, 'page' => $request->page]), 301);
        }

        // 'reviews' is needed by every product-card partial for its star rating
        // (avg) and count — without it, each card lazy-loads its own reviews query,
        // turning a 12-24-product listing page into 12-24+ extra DB round-trips.
        $query = Product::with(['category', 'brand', 'activeFlashSaleProduct', 'reviews'])->active();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('sku', 'like', "%$s%")
                ->orWhere('short_description', 'like', "%$s%")
            );
            // An actually-submitted search (landed here), not every autosuggest keystroke —
            // see SearchQuery::record()'s own docblock. Feeds the homepage personalized
            // section (HomeSection::getPersonalizedProducts()).
            SearchQuery::record($s, auth()->id(), session()->getId());
        }

        if ($request->filled('category')) {
            // Comma-separated so a homepage section scoped to several categories at
            // once (see HomeSection::getViewAllUrl()) can still deep-link its
            // "VIEW ALL" here — a single slug works the same as before.
            $slugs = explode(',', $request->category);
            $query->where(fn($q) => $q
                ->whereHas('category', fn($q2) => $q2->whereIn('slug', $slugs))
                ->orWhereHas('subcategory', fn($q2) => $q2->whereIn('slug', $slugs))
            );
        }

        if ($request->filled('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $request->brand));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price');
        }

        $sortBy = $request->get('sort', 'latest');
        match($sortBy) {
            'price_low'  => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'name'       => $query->orderBy('name', 'asc'),
            'popular'    => $query->orderBy('views', 'desc'),
            default      => $query->latest(),
        };

        $products   = $query->paginate(12)->withQueryString();
        $categories = Category::whereNull('parent_id')
            ->active()
            ->withCount('products')
            ->with(['children' => fn($q) => $q->active()->withCount('products')])
            ->orderBy('sort_order')
            ->get();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $tags       = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return view('shop.index', compact('products', 'categories', 'brands', 'tags'));
    }

    public function categories()
    {
        $categories = Category::whereNull('parent_id')
            ->active()
            ->withCount('products')
            ->with(['children' => fn($q) => $q->active()->withCount('products')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('shop.categories', compact('categories'));
    }

    public function brands()
    {
        $brands = Brand::where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('shop.brands', compact('brands'));
    }

    public function category(Category $category)
    {
        if ($category->redirect_url) {
            return redirect()->away($category->redirect_url, 301);
        }

        // A parent category (e.g. "Women's Shop") usually has no products of its own — they're
        // all filed under its subcategories — so its page used to come up completely empty
        // (and sat in the sitemap as a thin page). Include every descendant's products too.
        $ids = collect([$category->id]);
        $frontier = $ids;
        while ($frontier->isNotEmpty()) {
            $frontier = Category::active()->whereIn('parent_id', $frontier)->whereNotIn('id', $ids)->pluck('id');
            $ids = $ids->merge($frontier);
        }

        return $this->listing(fn ($q) => $q
            ->whereIn('category_id', $ids)
            ->orWhereIn('subcategory_id', $ids), compact('category'));
    }

    /**
     * Dedicated, indexable brand landing page (/brand/{slug}) — "<brand> price in
     * Bangladesh" style searches need a real URL with its own title/H1/description,
     * which a ?brand= filter on /shop can't give them.
     */
    public function brand(Brand $brand)
    {
        abort_unless($brand->is_active, 404);

        return $this->listing(fn ($q) => $q->where('brand_id', $brand->id), ['currentBrand' => $brand]);
    }

    private function listing(\Closure $scope, array $context)
    {
        $products = Product::with(['category', 'brand', 'activeFlashSaleProduct', 'reviews'])
            ->where($scope)
            ->active()
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::whereNull('parent_id')
            ->active()
            ->withCount('products')
            ->with(['children' => fn($q) => $q->active()->withCount('products')])
            ->orderBy('sort_order')
            ->get();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $tags       = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return view('shop.index', compact('products', 'categories', 'brands', 'tags') + $context);
    }
}
