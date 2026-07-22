<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashSale;
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

        $featuredProducts = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
            ->active()
            ->featured()
            ->latest()
            ->take(8)
            ->get();

        $newArrivals = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
            ->active()
            ->latest()
            ->take(16)
            ->get();

        $topSelling = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
            ->active()
            ->where('stock', '>', 0)
            ->orderByDesc('views')
            ->take(16)
            ->get();

        $onSale = Product::with('category', 'brand', 'reviews', 'activeFlashSaleProduct')
            ->active()
            ->whereNotNull('sale_price')
            ->orderByDesc('updated_at')
            ->take(16)
            ->get();

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
            'featuredProducts',
            'newArrivals',
            'topSelling',
            'onSale',
            'banners',
            'promoBanners',
            'brands',
            'flashSale',
            'flashSaleProducts',
            'testimonials',
        ));
    }
}
