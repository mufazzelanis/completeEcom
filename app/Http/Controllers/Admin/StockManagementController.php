<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockManagementController extends Controller
{
    /**
     * Shared by index() (paginated, for display) and ids() (every matching id, for "select
     * all N matching" to bulk-apply across pages) — kept in exactly one place so the two can
     * never quietly drift out of sync with each other.
     */
    /**
     * Quick stock buckets shown as chips (with live counts) — [label, min, max]; null = open.
     * "below" (each product's own low_stock_threshold) and "fast" (running out at the current
     * sales speed) are handled separately in applyStock().
     */
    public const STOCK_BUCKETS = [
        'out'   => ['Out of stock', 0, 0],
        's1'    => ['Only 1 left', 1, 1],
        's2'    => ['2 left', 2, 2],
        's3'    => ['3 left', 3, 3],
        'low'   => ['1 – 5', 1, 5],
        'b6'    => ['6 – 10', 6, 10],
        'b11'   => ['11 – 20', 11, 20],
        'ok'    => ['More than 20', 21, null],
    ];

    /** Units sold per product in the last 30 days (cancelled/refunded orders don't count). */
    private function soldSubquery()
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->whereNotIn('orders.status', ['cancelled', 'refunded'])
            ->groupBy('order_items.product_id')
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as sold_30d'));
    }

    /** Every filter except the stock one — the chips' counts are computed under these. */
    private function baseFiltered(Request $request)
    {
        $query = Product::query()
            ->leftJoinSub($this->soldSubquery(), 'sales', 'sales.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(sales.sold_30d, 0) as sold_30d'));

        if ($request->filled('search')) {
            $q = '%' . addcslashes(trim($request->search), '%_\\') . '%';
            $query->where(fn ($qr) => $qr->where('products.name', 'like', $q)->orWhere('products.sku', 'like', $q));
        }
        if ($request->filled('category')) {
            // a top-level category includes its sub-categories
            $ids = \App\Models\Category::where('parent_id', $request->category)->pluck('id')->push((int) $request->category);
            $query->where(fn ($qr) => $qr->whereIn('products.category_id', $ids)->orWhereIn('products.subcategory_id', $ids));
        }
        if ($request->filled('brand')) {
            $query->where('products.brand_id', $request->brand);
        }
        if ($request->input('status') === 'active') {
            $query->where('products.is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('products.is_active', false);
        }

        return $query;
    }

    private function applyStock($query, Request $request)
    {
        $bucket = $request->input('stock_filter');
        if (isset(self::STOCK_BUCKETS[$bucket])) {
            [, $min, $max] = self::STOCK_BUCKETS[$bucket];
            $query->where('products.stock', '>=', $min);
            if ($max !== null) {
                $query->where('products.stock', '<=', $max);
            }
        } elseif ($bucket === 'below') {
            $query->whereColumn('products.stock', '<=', 'products.low_stock_threshold');
        } elseif ($bucket === 'fast') {
            // at the last 30 days' pace this runs out within 14 days (and it does sell)
            $query->whereRaw('COALESCE(sales.sold_30d, 0) > 0')->whereRaw('products.stock * 30 <= COALESCE(sales.sold_30d, 0) * 14');
        }
        // custom range: "exactly 3" is min 3 + max 3
        if ($request->filled('min') && is_numeric($request->min)) {
            $query->where('products.stock', '>=', (int) $request->min);
        }
        if ($request->filled('max') && is_numeric($request->max)) {
            $query->where('products.stock', '<=', (int) $request->max);
        }

        return $query;
    }

    private function filteredProducts(Request $request)
    {
        $query = $this->applyStock($this->baseFiltered($request)->with('category'), $request);

        return match ($request->input('sort')) {
            'stock_desc' => $query->orderByDesc('products.stock'),
            'name'       => $query->orderBy('products.name'),
            'selling'    => $query->orderByDesc('sold_30d')->orderBy('products.stock'),
            'newest'     => $query->latest('products.created_at'),
            default      => $query->orderBy('products.stock')->orderBy('products.name'),
        };
    }

    /** How many products fall in each chip, under the other filters currently applied. */
    private function bucketCounts(Request $request): array
    {
        $counts = [];
        foreach (array_keys(self::STOCK_BUCKETS) as $key) {
            $counts[$key] = $this->applyStock($this->baseFiltered($request), new Request(['stock_filter' => $key]))->count();
        }
        $counts['below'] = $this->applyStock($this->baseFiltered($request), new Request(['stock_filter' => 'below']))->count();
        $counts['fast'] = $this->applyStock($this->baseFiltered($request), new Request(['stock_filter' => 'fast']))->count();
        $counts['all'] = $this->baseFiltered($request)->count();

        return $counts;
    }

    public function index(Request $request)
    {
        $products   = $this->filteredProducts($request)->paginate(30)->withQueryString();
        $categories = \App\Models\Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
        $reasons    = StockReason::active()
            ->whereIn('type', ['any', 'manual_in', 'manual_out'])
            ->orderBy('sort_order')
            ->get(['id', 'label']);

        $brands = \App\Models\Brand::orderBy('name')->get(['id', 'name']);
        $counts = $this->bucketCounts($request);

        return view('admin.stock_management.index', compact('products', 'categories', 'reasons', 'brands', 'counts'));
    }

    /**
     * Every product id matching the current filters (search/stock_filter/category),
     * regardless of pagination — what "Select all N matching products" on the bulk-apply
     * bar actually selects, so a whole category's restock isn't capped at one page of 30.
     */
    public function ids(Request $request)
    {
        return response()->json([
            'ids' => $this->filteredProducts($request)->pluck('products.id'),
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
