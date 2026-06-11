<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('warehouse_id')) {
            $query->where(fn($q) => $q
                ->where('from_warehouse_id', $request->warehouse_id)
                ->orWhere('to_warehouse_id', $request->warehouse_id)
            );
        }
        if ($request->filled('search')) {
            $query->where('reference_no', 'like', '%' . $request->search . '%');
        }

        $transfers  = $query->latest()->paginate(20)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.stock_transfers.index', compact('transfers', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id'   => $request->to_warehouse_id,
                'status'            => 'pending',
                'notes'             => $request->notes,
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                StockTransferItem::create([
                    'transfer_id'          => $transfer->id,
                    'product_id'           => $item['product_id'],
                    'quantity_requested'   => $item['quantity'],
                    'quantity_transferred' => 0,
                ]);
            }
        });

        return redirect()->route('admin.stock-transfers.index')
            ->with('success', 'Stock transfer created.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'items.product']);
        return view('admin.stock_transfers.show', compact('stockTransfer'));
    }

    public function dispatch(StockTransfer $stockTransfer)
    {
        if (!in_array($stockTransfer->status, ['pending', 'draft'])) {
            return back()->with('error', 'Transfer cannot be dispatched.');
        }

        $stockTransfer->update(['status' => 'in_transit']);

        return back()->with('success', 'Transfer marked as In Transit.');
    }

    public function complete(Request $request, StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'in_transit') {
            return back()->with('error', 'Only in-transit transfers can be completed.');
        }

        $request->validate([
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $stockTransfer) {
            foreach ($stockTransfer->items as $item) {
                $qty = (int) ($request->quantities[$item->id] ?? $item->quantity_requested);

                // Deduct from source warehouse
                $from = WarehouseStock::firstOrCreate(
                    ['warehouse_id' => $stockTransfer->from_warehouse_id, 'product_id' => $item->product_id],
                    ['stock' => 0]
                );
                $from->decrement('stock', min($qty, $from->stock));

                // Add to destination warehouse
                WarehouseStock::updateOrCreate(
                    ['warehouse_id' => $stockTransfer->to_warehouse_id, 'product_id' => $item->product_id],
                    ['stock' => 0]
                );
                WarehouseStock::where('warehouse_id', $stockTransfer->to_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->increment('stock', $qty);

                $item->update(['quantity_transferred' => $qty]);
            }

            $stockTransfer->update(['status' => 'completed', 'completed_at' => now()]);
        });

        return redirect()->route('admin.stock-transfers.show', $stockTransfer)
            ->with('success', 'Transfer completed. Warehouse stock updated.');
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status === 'completed') {
            return back()->with('error', 'Completed transfers cannot be cancelled.');
        }

        $stockTransfer->update(['status' => 'cancelled']);

        return back()->with('success', 'Transfer cancelled.');
    }
}
