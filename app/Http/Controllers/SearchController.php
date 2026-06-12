<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function suggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $products = Product::active()
            ->where(fn($qb) => $qb
                ->where('name', 'like', "%$q%")
                ->orWhere('sku', 'like', "%$q%")
                ->orWhere('short_description', 'like', "%$q%")
            )
            ->limit(6)
            ->get(['id', 'name', 'slug', 'image', 'price', 'sale_price']);

        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%$q%")
            ->limit(4)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => $products->map(fn($p) => [
                'name'  => $p->name,
                'price' => '৳' . number_format($p->sale_price ?? $p->price),
                'image' => $p->image ? asset('storage/' . $p->image) : null,
                'url'   => route('products.show', $p->slug),
            ]),
            'categories' => $categories->map(fn($c) => [
                'name' => $c->name,
                'url'  => route('shop.category', $c->slug),
            ]),
        ]);
    }

    public function adminSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::where(fn($qb) => $qb
            ->where('name', 'like', "%$q%")
            ->orWhere('sku', 'like', "%$q%")
            ->orWhere('barcode', 'like', "%$q%")
        )
        ->limit(8)
        ->get(['id', 'name', 'sku', 'image', 'price', 'is_active']);

        return response()->json([
            'products' => $products->map(fn($p) => [
                'name'      => $p->name,
                'sku'       => $p->sku ?? 'No SKU',
                'price'     => '৳' . number_format($p->price),
                'image'     => $p->image ? asset('storage/' . $p->image) : null,
                'is_active' => (bool) $p->is_active,
                'url'       => route('admin.products.edit', $p->id),
            ]),
        ]);
    }
}
