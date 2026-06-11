<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SkuManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%"));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('has_sku')) {
            if ($request->has_sku === 'yes') {
                $query->whereNotNull('sku')->where('sku', '!=', '');
            } else {
                $query->where(fn($q) => $q->whereNull('sku')->orWhere('sku', ''));
            }
        }

        $products   = $query->orderBy('name')->paginate(30)->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $noSkuCount = Product::where(fn($q) => $q->whereNull('sku')->orWhere('sku', ''))->count();

        return view('admin.sku_management.index', compact('products', 'categories', 'noSkuCount'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'skus'   => 'required|array',
            'skus.*' => 'nullable|string|max:100',
        ]);

        foreach ($request->skus as $id => $sku) {
            $clean = trim($sku ?? '');
            if ($clean === '') {
                Product::where('id', $id)->update(['sku' => null]);
            } else {
                // Only update if no conflict
                $conflict = Product::where('sku', $clean)->where('id', '!=', $id)->exists();
                if (!$conflict) {
                    Product::where('id', $id)->update(['sku' => $clean]);
                }
            }
        }

        return back()->with('success', 'SKUs saved.');
    }

    public function generate(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $product = Product::findOrFail($request->product_id);
        $prefix  = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $product->name), 0, 4));
        $sku     = $prefix . '-' . str_pad($product->id, 5, '0', STR_PAD_LEFT);

        if (!Product::where('sku', $sku)->where('id', '!=', $product->id)->exists()) {
            $product->update(['sku' => $sku]);
        }

        return back()->with('success', 'SKU generated for ' . $product->name);
    }
}
