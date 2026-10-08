<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Bumped (see AppServiceProvider) whenever a model the homepage shows is saved or deleted,
     * which orphans every cached homepage block at once — admin edits show up immediately,
     * and the TTL below only bounds staleness from changes that bypass model events.
     */
    public const CACHE_VERSION_KEY = 'home_cache_version';
    // 5 min: edits invalidate instantly anyway (see AppServiceProvider), so this only bounds
    // time-based changes no save event announces — a banner reaching its starts_at/ends_at —
    // and raw DB edits. Shorter would mostly just mean more cold renders on a low-traffic site.
    private const CACHE_TTL = 300; // seconds

    private ?int $cacheVersion = null;

    private function remember(string $key, \Closure $callback)
    {
        $version = $this->cacheVersion ??= Cache::get(self::CACHE_VERSION_KEY, 1);

        return Cache::remember("home:{$version}:{$key}", self::CACHE_TTL, $callback);
    }

    public function index()
    {
        // Everything on the homepage that's the same for every visitor is cached (short TTL,
        // plus version-bump invalidation on edits) — the page ran ~20 queries per visit for
        // data that changes a few times a day. Per-visitor parts (personalized section, flash
        // sale countdown) stay live.
        $categories = $this->remember('categories', fn () => Category::where('is_active', true)
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->take(12)
            ->get());

        $subcategories = $this->remember('subcategories', fn () => Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(20)
            ->get());

        // Homepage product sections (Featured, Top Selling, New Arrivals, On Sale, and any
        // custom sections) are admin-managed via Admin → Homepage Sections, each with its
        // own product source, optional category filter, and display limit. Only product_limit
        // + 1 products are kept per section — the +1 just tells the view "See More" has
        // something to load; the overflow itself comes from sectionProducts() on demand.
        $sharedSections = $this->remember('sections', fn () => HomeSection::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($section) => $section->source_type === 'personalized' ? ['section' => $section] : [
                'section' => $section,
                'products' => $section->getProducts()->take($section->product_limit + 1)->values(),
                'totalCount' => $section->getTotalAvailableCount(),
            ]));

        $homeSections = $sharedSections
            ->map(fn ($entry) => isset($entry['products']) ? $entry : [
                'section' => $entry['section'],
                'products' => $entry['section']->getProducts(),
                'totalCount' => $entry['section']->getTotalAvailableCount(),
            ])
            ->filter(fn ($entry) => $entry['products']->isNotEmpty())
            ->values();

        // "Just For You" reuses the New Arrivals section's overflow (whatever comes after
        // what that section itself displays) so the two blocks never repeat products.
        $newArrivalsEntry = $homeSections->first(fn ($entry) => $entry['section']->source_type === 'new_arrivals');
        $justForYou = collect();
        if ($newArrivalsEntry) {
            $justForYou = $this->remember('just_for_you', fn () => Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
                ->active()
                ->latest()
                ->skip($newArrivalsEntry['section']->product_limit)
                ->take(10)
                ->get());
        }

        $banners = $this->remember('banners', fn () => Banner::active()
            ->position('hero')
            ->orderBy('sort_order')
            ->get());

        $promoBanners = $this->remember('promo_banners', fn () => Banner::active()
            ->position('top')
            ->orderBy('sort_order')
            ->take(4)
            ->get());

        $brands = $this->remember('brands', fn () => Brand::where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(20)
            ->get());

        $testimonials = $this->remember('testimonials', fn () => Review::with('user', 'product')
            ->where('is_approved', true)
            ->whereHas('user')
            ->latest()
            ->take(15)
            ->get());

        $flashSale = FlashSale::current();
        $flashSaleProducts = collect();
        if ($flashSale) {
            $flashSaleProducts = $flashSale->products()
                ->with('product.category', 'product.brand', 'product.reviews')
                ->get()
                ->filter(fn ($fsp) => $fsp->product && $fsp->product->is_active);
        }

        return view('home', compact(
            'categories',
            'subcategories',
            'homeSections',
            'justForYou',
            'banners',
            'promoBanners',
            'brands',
            'flashSale',
            'flashSaleProducts',
            'testimonials',
        ));
    }

    /**
     * "See More" on a homepage section: the next batch of that section's product cards as
     * HTML. The homepage used to render every section's full 40-product batch up front and
     * just hide the overflow — ~280 cards / 1.7 MB of HTML for a first view showing a few
     * dozen — so the overflow is now fetched only when someone actually asks for it.
     */
    public function sectionProducts(Request $request, HomeSection $section)
    {
        abort_unless($section->is_active, 404);

        $offset = max(0, $request->integer('offset'));
        $count = min(24, max(1, $request->integer('count', 8)));
        $all = $section->getProducts();
        $products = $all->slice($offset, $count);

        return response()->json([
            'html' => $products->map(fn ($product) => view('partials.product-card', ['product' => $product])->render())->implode(''),
            'next' => $offset + $products->count(),
            'hasMore' => $offset + $products->count() < $all->count(),
        ]);
    }
}
