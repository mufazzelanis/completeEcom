<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockReason;
use Illuminate\Http\Request;

class StockManagementController extends Controller
{
    /**
     * Shared by index() (paginated, for display) and ids() (every matching id, for "select
     * all N matching" to bulk-apply across pages) — kept in exactly one place so the two can
     * never quietly drift out of sync with each other.
     */
    private function filteredProducts(Request $request)
    {
        $query = Product::with('category')
            ->orderBy('stock', 'asc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%$q%")
                   ->orWhere('sku', 'like', "%$q%");
            });
        }
        if ($request->filled('stock_filter')) {
            match($request->stock_filter) {
                'out'  => $query->where('stock', 0),
                'low'  => $query->where('stock', '>', 0)->where('stock', '<=', 5),
                'ok'   => $query->where('stock', '>', 5),
                default => null,
            };
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $products   = $this->filteredProducts($request)->paginate(30)->withQueryString();
        $categories = \App\Models\Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $reasons    = StockReason::active()
            ->whereIn('type', ['any', 'manual_in', 'manual_out'])
            ->orderBy('sort_order')
            ->get(['id', 'label']);

        return view('admin.stock_management.index', compact('products', 'categories', 'reasons'));
    }

    /**
     * Every product id matching the current filters (search/stock_filter/category),
     * regardless of pagination — what "Select all N matching products" on the bulk-apply
     * bar actually selects, so a whole category's restock isn't capped at one page of 30.
     */
    public function ids(Request $request)
    {
        return response()->json([
            'ids' => $this->filteredProducts($request)->pluck('id'),
        ]);
    }

    /**
     * Applies ONE stock change to every selected product at once — set to an exact value,
     * or increase/decrease by a fixed amount (the common "a new shipment of 50 units landed
     * for this whole category" or "these all got recalled/damaged" cases), rather than
     * needing to hand-type a new total into every row like the per-row form below still does
     * for one-off corrections.
     */
    public function bulkApply(Request $request)
    {
        $validated = $request->validate([
            'ids'      => 'required|array|min:1',
            'ids.*'    => 'integer|exists:products,id',
            'mode'     => 'required|in:set,increase,decrease',
            'value'    => 'required|integer|min:0',
            'reason'   => 'nullable|string|max:500',
        ]);

        $updated = 0;

        foreach (Product::whereIn('id', $validated['ids'])->get() as $product) {
            $before = $product->stock;
            $newQty = match ($validated['mode']) {
                'set'      => $validated['value'],
                'increase' => $before + $validated['value'],
                'decrease' => max(0, $before - $validated['value']),
            };

            if ($newQty === $before) {
                continue;
            }

            $diff = $newQty - $before;
            $product->update(['stock' => $newQty]);

            StockAdjustment::create([
                'product_id'   => $product->id,
                'type'         => $diff > 0 ? 'manual_in' : 'manual_out',
                'quantity'     => abs($diff),
                'stock_before' => $before,
                'stock_after'  => $newQty,
                'reference'    => 'BULK-' . date('Ymd'),
                'reason'       => $validated['reason'] ?: match ($validated['mode']) {
                    'set'      => "Bulk set to {$validated['value']}",
                    'increase' => "Bulk increase by {$validated['value']}",
                    'decrease' => "Bulk decrease by {$validated['value']}",
                },
                'adjusted_by'  => auth()->id(),
            ]);

            $updated++;
        }

        $skipped = count($validated['ids']) - $updated;
        $message = "{$updated} product(s) stock updated in bulk.";
        if ($skipped > 0) {
            $message .= " {$skipped} already matched the target value and were left unchanged.";
        }

        return redirect()->route('admin.stock-management.index')->with('success', $message);
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

            $product->update(['stock' => $newQty]);

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
