<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'activeFlashSaleProduct'])->active();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('sku', 'like', "%$s%")
                ->orWhere('short_description', 'like', "%$s%")
            );
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

        $products = Product::with(['category', 'brand', 'activeFlashSaleProduct'])
            ->where(fn($q) => $q
                ->where('category_id', $category->id)
                ->orWhere('subcategory_id', $category->id)
            )
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

        return view('shop.index', compact('products', 'categories', 'brands', 'tags', 'category'));
    }
}
