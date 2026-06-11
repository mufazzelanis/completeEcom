<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['product', 'adjustedBy']);

        if ($request->filled('product')) {
            $query->where('product_id', $request->product);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->latest()->paginate(25);
        $products    = Product::orderBy('name')->get(['id', 'name']);
        return view('admin.stock_adjustments.index', compact('adjustments', 'products'));
    }

    public function create(Request $request)
    {
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'stock']);
        $orders   = Order::whereIn('status', ['delivered', 'completed'])
            ->latest()->limit(100)->get(['id', 'order_number']);
        $selectedType = $request->get('type', 'manual_in');
        return view('admin.stock_adjustments.create', compact('products', 'orders', 'selectedType'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:return_in,damage_out,manual_in,manual_out',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string|max:500',
        ]);

        $product = Product::findOrFail($request->product_id);
        $qty     = (int) $request->quantity;
        $isOut   = in_array($request->type, ['damage_out', 'manual_out']);

        if ($isOut && $product->stock < $qty) {
            return back()->withErrors(['quantity' => "Only {$product->stock} units in stock. Cannot remove {$qty}."])->withInput();
        }

        DB::transaction(function () use ($request, $product, $qty, $isOut) {
            $before = $product->stock;
            $isOut ? $product->decrement('stock', $qty) : $product->increment('stock', $qty);

            StockAdjustment::create([
                'product_id'  => $product->id,
                'order_id'    => $request->order_id ?: null,
                'type'        => $request->type,
                'quantity'    => $isOut ? -$qty : $qty,
                'stock_before'=> $before,
                'stock_after' => $isOut ? $before - $qty : $before + $qty,
                'reference'   => $request->reference,
                'reason'      => $request->reason,
                'adjusted_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.stock-adjustments.index')
            ->with('success', 'Stock adjusted successfully.');
    }
}
