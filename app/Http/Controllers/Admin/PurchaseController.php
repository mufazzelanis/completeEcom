<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with('supplier')->withCount('items');

        if ($request->filled('search')) {
            $query->where('reference_no', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('supplier')) {
            $query->where('supplier_id', $request->supplier);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(20);
        $suppliers  = Supplier::orderBy('name')->get(['id', 'name']);
        return view('admin.purchases.index', compact('purchases', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company']);
        $products  = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'price']);
        $reference = Purchase::generateReference();
        return view('admin.purchases.create', compact('suppliers', 'products', 'reference'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchased_at'  => 'required|date',
            'items'         => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost'        => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $total = 0;
            foreach ($request->items as $row) {
                $total += $row['quantity_ordered'] * $row['unit_cost'];
            }

            $purchase = Purchase::create([
                'supplier_id'   => $request->supplier_id,
                'reference_no'  => Purchase::generateReference(),
                'status'        => $request->input('status', 'ordered'),
                'total_amount'  => $total,
                'paid_amount'   => $request->paid_amount ?? 0,
                'notes'         => $request->notes,
                'purchased_at'  => $request->purchased_at,
                'created_by'    => auth()->id(),
            ]);

            foreach ($request->items as $row) {
                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $row['product_id'],
                    'quantity_ordered'  => $row['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost'         => $row['unit_cost'],
                    'total_cost'        => $row['quantity_ordered'] * $row['unit_cost'],
                ]);
            }

            // If status is 'received', auto-apply stock
            if ($purchase->status === 'received') {
                $this->applyStock($purchase);
            }
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase order created.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'creator']);
        return view('admin.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        if (in_array($purchase->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Cannot edit a received or cancelled purchase.');
        }
        $purchase->load('items.product');
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company']);
        $products  = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'price']);
        return view('admin.purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        if (in_array($purchase->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Cannot edit a received or cancelled purchase.');
        }

        $request->validate([
            'supplier_id'  => 'required|exists:suppliers,id',
            'purchased_at' => 'required|date',
            'items'        => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost'        => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchase) {
            $total = 0;
            foreach ($request->items as $row) {
                $total += $row['quantity_ordered'] * $row['unit_cost'];
            }

            $purchase->update([
                'supplier_id'  => $request->supplier_id,
                'status'       => $request->status,
                'total_amount' => $total,
                'paid_amount'  => $request->paid_amount ?? 0,
                'notes'        => $request->notes,
                'purchased_at' => $request->purchased_at,
            ]);

            $purchase->items()->delete();
            foreach ($request->items as $row) {
                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'product_id'        => $row['product_id'],
                    'quantity_ordered'  => $row['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost'         => $row['unit_cost'],
                    'total_cost'        => $row['quantity_ordered'] * $row['unit_cost'],
                ]);
            }
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase updated.');
    }

    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status === 'cancelled') {
            return back()->with('error', 'Cannot receive a cancelled purchase.');
        }
        if ($purchase->status === 'received') {
            return back()->with('error', 'Purchase already marked as received.');
        }

        DB::transaction(function () use ($request, $purchase) {
            foreach ($purchase->items as $item) {
                $qty = (int) ($request->input("quantities.{$item->id}", $item->quantity_ordered));
                $item->update([
                    'quantity_received' => $qty,
                    'total_cost'        => $qty * $item->unit_cost,
                ]);
            }

            $purchase->refresh();
            $totalReceived = $purchase->items->sum('quantity_received');
            $totalOrdered  = $purchase->items->sum('quantity_ordered');

            $status = $totalReceived >= $totalOrdered ? 'received' : 'partial';
            $purchase->update([
                'status'       => $status,
                'received_at'  => now()->toDateString(),
                'total_amount' => $purchase->items->sum('total_cost'),
            ]);

            $this->applyStock($purchase);
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Stock updated from purchase receipt.');
    }

    public function destroy(Purchase $purchase)
    {
        if (in_array($purchase->status, ['received', 'partial'])) {
            return back()->with('error', 'Cannot delete a purchase that has received stock.');
        }
        $purchase->items()->delete();
        $purchase->delete();
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase deleted.');
    }

    private function applyStock(Purchase $purchase): void
    {
        foreach ($purchase->items as $item) {
            $qty = $item->quantity_received ?: $item->quantity_ordered;
            if ($qty <= 0) continue;

            $product = $item->product;
            $before  = $product->stock;
            $product->increment('stock', $qty);

            StockAdjustment::create([
                'product_id'  => $product->id,
                'type'        => 'purchase_in',
                'quantity'    => $qty,
                'stock_before'=> $before,
                'stock_after' => $before + $qty,
                'reference'   => $purchase->reference_no,
                'reason'      => 'Purchase received: ' . $purchase->reference_no,
                'adjusted_by' => auth()->id(),
            ]);
        }
    }
}
