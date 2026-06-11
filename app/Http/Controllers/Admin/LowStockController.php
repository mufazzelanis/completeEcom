<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class LowStockController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'low'); // low | out | all

        $query = Product::with('category')
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true);

        if ($filter === 'out') {
            $query->where('stock', 0);
        } elseif ($filter === 'low') {
            $query->where('stock', '>', 0);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%"));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->orderBy('stock')->paginate(25)->withQueryString();

        $outCount  = Product::where('is_active', true)->where('stock', 0)->count();
        $lowCount  = Product::where('is_active', true)->where('stock', '>', 0)
                        ->whereColumn('stock', '<=', 'low_stock_threshold')->count();

        $categories = \App\Models\Category::orderBy('name')->get(['id', 'name']);

        return view('admin.low_stock.index', compact('products', 'outCount', 'lowCount', 'filter', 'categories'));
    }

    public function updateThreshold(Request $request, Product $product)
    {
        $request->validate(['threshold' => 'required|integer|min:0']);
        $product->update(['low_stock_threshold' => $request->threshold]);

        return back()->with('success', 'Threshold updated for ' . $product->name);
    }
}
