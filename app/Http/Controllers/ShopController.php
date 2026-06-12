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
        $query = Product::with(['category', 'brand'])->active();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('sku', 'like', "%$s%")
                ->orWhere('short_description', 'like', "%$s%")
            );
        }

        if ($request->filled('category')) {
            $slug = $request->category;
            $query->where(fn($q) => $q
                ->whereHas('category', fn($q2) => $q2->where('slug', $slug))
                ->orWhereHas('subcategory', fn($q2) => $q2->where('slug', $slug))
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
            ->where('is_active', true)
            ->withCount('products')
            ->with(['children' => fn($q) => $q->where('is_active', true)->withCount('products')])
            ->orderBy('sort_order')
            ->get();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $tags       = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return view('shop.index', compact('products', 'categories', 'brands', 'tags'));
    }

    public function category(Category $category)
    {
        $products = Product::with(['category', 'brand'])
            ->where(fn($q) => $q
                ->where('category_id', $category->id)
                ->orWhere('subcategory_id', $category->id)
            )
            ->active()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('products')
            ->with(['children' => fn($q) => $q->where('is_active', true)->withCount('products')])
            ->orderBy('sort_order')
            ->get();
        $brands     = Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        $tags       = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return view('shop.index', compact('products', 'categories', 'brands', 'tags', 'category'));
    }
}
