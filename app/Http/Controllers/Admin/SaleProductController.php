<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class SaleProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%$q%")
                   ->orWhere('sku', 'like', "%$q%");
            });
        }
        if ($request->filled('sale_filter')) {
            if ($request->sale_filter === 'on_sale') {
                $query->whereNotNull('sale_price')->where('sale_price', '>', 0);
            } else {
                $query->where(fn($q) => $q->whereNull('sale_price')->orWhere('sale_price', 0));
            }
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products    = $query->orderBy('name')->paginate(25)->withQueryString();
        $categories  = \App\Models\Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $onSaleCount = Product::whereNotNull('sale_price')->where('sale_price', '>', 0)->count();

        return view('admin.sale_products.index', compact('products', 'categories', 'onSaleCount'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'sale_price' => [
                'nullable', 'numeric', 'min:0',
                function ($attribute, $value, $fail) use ($product) {
                    if ($value && $value >= $product->price) {
                        $fail("Sale price must be less than the regular price (৳{$product->price}).");
                    }
                },
            ],
        ]);

        $product->update([
            'sale_price' => $request->sale_price ?: null,
        ]);

        return back()->with('success', $request->sale_price
            ? "Sale price set for {$product->name}."
            : "Sale removed for {$product->name}.");
    }

    public function clearAll(Request $request)
    {
        $ids = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:products,id',
        ])['ids'];

        Product::whereIn('id', $ids)->update(['sale_price' => null]);

        return back()->with('success', count($ids) . ' sale price(s) cleared.');
    }
}
