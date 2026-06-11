<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Batch::with(['product', 'warehouse']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('search')) {
            $query->where('lot_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            if ($request->status === 'expired') {
                $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
            } elseif ($request->status === 'expiring') {
                $query->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', now())
                    ->whereDate('expiry_date', '<=', now()->addDays(30));
            } elseif ($request->status === 'active') {
                $query->where(fn($q) => $q->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>', now()->addDays(30)));
            }
        }

        $batches    = $query->orderBy('expiry_date')->latest()->paginate(25)->withQueryString();
        $products   = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $expiredCount  = Batch::whereNotNull('expiry_date')->whereDate('expiry_date', '<', now())->count();
        $expiringCount = Batch::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))->count();

        return view('admin.batches.index', compact(
            'batches', 'products', 'warehouses', 'expiredCount', 'expiringCount'
        ));
    }

    public function create()
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.batches.create', compact('products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'warehouse_id'     => 'nullable|exists:warehouses,id',
            'lot_number'       => 'required|string|max:100',
            'quantity'         => 'required|integer|min:0',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:manufacture_date',
            'notes'            => 'nullable|string',
            'is_active'        => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Batch::create($data);

        return redirect()->route('admin.batches.index')
            ->with('success', 'Batch created.');
    }

    public function edit(Batch $batch)
    {
        $products   = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.batches.edit', compact('batch', 'products', 'warehouses'));
    }

    public function update(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'warehouse_id'     => 'nullable|exists:warehouses,id',
            'lot_number'       => 'required|string|max:100',
            'quantity'         => 'required|integer|min:0',
            'manufacture_date' => 'nullable|date',
            'expiry_date'      => 'nullable|date|after_or_equal:manufacture_date',
            'notes'            => 'nullable|string',
            'is_active'        => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $batch->update($data);

        return redirect()->route('admin.batches.index')
            ->with('success', 'Batch updated.');
    }

    public function destroy(Batch $batch)
    {
        $batch->delete();
        return back()->with('success', 'Batch deleted.');
    }
}
