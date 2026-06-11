<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;

class StockManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->orderBy('stock_quantity', 'asc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%$q%")
                   ->orWhere('sku', 'like', "%$q%");
            });
        }
        if ($request->filled('stock_filter')) {
            match($request->stock_filter) {
                'out'  => $query->where('stock_quantity', 0),
                'low'  => $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 5),
                'ok'   => $query->where('stock_quantity', '>', 5),
                default => null,
            };
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products   = $query->paginate(30)->withQueryString();
        $categories = \App\Models\Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);

        return view('admin.stock_management.index', compact('products', 'categories'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'products'          => 'required|array',
            'products.*.id'     => 'required|integer|exists:products,id',
            'products.*.stock'  => 'required|integer|min:0',
            'products.*.reason' => 'nullable|string|max:500',
        ]);

        $updated = 0;
        foreach ($request->products as $row) {
            $product = Product::find($row['id']);
            if (!$product) continue;

            $newQty = (int) $row['stock'];
            $before = $product->stock;
            if ($before === $newQty) continue;

            $diff = $newQty - $before;
            $type = $diff > 0 ? 'manual_in' : 'manual_out';

            $product->update(['stock_quantity' => $newQty]);

            StockAdjustment::create([
                'product_id'  => $product->id,
                'type'        => $type,
                'quantity'    => abs($diff),
                'stock_before'=> $before,
                'stock_after' => $newQty,
                'reference'   => 'MANUAL-' . date('Ymd'),
                'reason'      => $row['reason'] ?? 'Manual stock update',
                'adjusted_by' => auth()->id(),
            ]);

            $updated++;
        }

        return redirect()->route('admin.stock-management.index')
            ->with('success', "$updated product(s) stock updated successfully.");
    }

    public function quickUpdate(Request $request, Product $product)
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'reason'         => 'nullable|string|max:500',
        ]);

        $before = $product->stock;
        $newQty = (int) $request->stock_quantity;

        if ($before !== $newQty) {
            $diff = $newQty - $before;
            $product->update(['stock' => $newQty]);

            StockAdjustment::create([
                'product_id'  => $product->id,
                'type'        => $diff > 0 ? 'manual_in' : 'manual_out',
                'quantity'    => abs($diff),
                'stock_before'=> $before,
                'stock_after' => $newQty,
                'reference'   => 'QUICK-' . date('Ymd'),
                'reason'      => $request->reason ?? 'Quick stock update',
                'adjusted_by' => auth()->id(),
            ]);
        }

        return back()->with('success', "Stock updated to {$newQty} for {$product->name}.");
    }
}
