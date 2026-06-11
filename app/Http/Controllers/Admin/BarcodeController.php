<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$s%")
                ->orWhere('sku', 'like', "%$s%")
                ->orWhere('barcode', 'like', "%$s%")
            );
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('has_barcode')) {
            if ($request->has_barcode === 'yes') {
                $query->whereNotNull('barcode')->where('barcode', '!=', '');
            } else {
                $query->where(fn($q) => $q->whereNull('barcode')->orWhere('barcode', ''));
            }
        }

        $products   = $query->orderBy('name')->paginate(24)->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.barcodes.index', compact('products', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate(['barcode' => 'nullable|string|max:100']);
        $product->update(['barcode' => $request->barcode]);

        return back()->with('success', 'Barcode updated for ' . $product->name);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'barcodes'   => 'required|array',
            'barcodes.*' => 'nullable|string|max:100',
        ]);

        foreach ($request->barcodes as $id => $barcode) {
            Product::where('id', $id)->update(['barcode' => $barcode ?: null]);
        }

        return back()->with('success', 'Barcodes saved.');
    }
}
