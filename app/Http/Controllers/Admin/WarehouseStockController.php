<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('sort_order')->get();
        $selectedWarehouse = null;

        $query = Product::with('category');

        if ($request->filled('warehouse_id')) {
            $selectedWarehouse = Warehouse::findOrFail($request->warehouse_id);
            $query->with(['warehouseStock' => fn($q) => $q->where('warehouse_id', $request->warehouse_id)]);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%"));
        }

        $products = $query->where('is_active', true)->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.warehouse_stock.index', compact('warehouses', 'selectedWarehouse', 'products'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'warehouse_id'    => 'required|exists:warehouses,id',
            'stocks'          => 'required|array',
            'stocks.*'        => 'nullable|integer|min:0',
        ]);

        $warehouseId = $request->warehouse_id;

        foreach ($request->stocks as $productId => $qty) {
            if ($qty === null) continue;
            WarehouseStock::updateOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['stock' => (int) $qty]
            );
        }

        return back()->with('success', 'Warehouse stock updated.');
    }
}
