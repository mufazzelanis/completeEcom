<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->take(12)
            ->get();

        $subcategories = Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(20)
            ->get();

        // Homepage product sections (Featured, Top Selling, New Arrivals, On Sale, and any
        // custom sections) are admin-managed via Admin → Homepage Sections, each with its
        // own product source, optional category filter, and display limit.
        $homeSections = HomeSection::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($section) => ['section' => $section, 'products' => $section->getProducts()])
            ->filter(fn ($entry) => $entry['products']->isNotEmpty())
            ->values();

        // "Just For You" reuses the New Arrivals section's overflow (whatever comes after
        // what that section itself displays) so the two blocks never repeat products.
        $newArrivalsEntry = $homeSections->first(fn ($entry) => $entry['section']->source_type === 'new_arrivals');
        $justForYou = collect();
        if ($newArrivalsEntry) {
            $justForYou = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
                ->active()
                ->latest()
                ->skip($newArrivalsEntry['section']->product_limit)
                ->take(10)
                ->get();
        }

        $banners = Banner::active()
            ->position('hero')
            ->orderBy('sort_order')
            ->get();

        $promoBanners = Banner::active()
            ->position('top')
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        $brands = Brand::where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(20)
            ->get();

        $testimonials = Review::with('user', 'product')
            ->where('is_approved', true)
            ->whereHas('user')
            ->latest()
            ->take(15)
            ->get();

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
}
