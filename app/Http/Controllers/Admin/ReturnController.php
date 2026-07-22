<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReturn;
use App\Models\StockAdjustment;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductReturn::with(['order', 'user', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qr) use ($q) {
                $qr->where('return_number', 'like', "%$q%")
                   ->orWhereHas('order', fn($o) => $o->where('order_number', 'like', "%$q%"))
                   ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$q%"));
            });
        }

        $returns = $query->paginate(20);

        $stats = [
            'pending'  => ProductReturn::where('status', 'pending')->count(),
            'approved' => ProductReturn::where('status', 'approved')->count(),
            'total'    => ProductReturn::count(),
        ];

        return view('admin.returns.index', compact('returns', 'stats'));
    }

    public function show(int $id)
    {
        $return = ProductReturn::with(['order.items', 'user', 'items.product', 'processedBy'])
            ->findOrFail($id);
        return view('admin.returns.show', compact('return'));
    }

    public function approve(Request $request, int $id)
    {
        $return = ProductReturn::with('items.product')->findOrFail($id);

        if ($return->status !== 'pending') {
            return back()->with('error', 'Return is not in pending state.');
        }

        $request->validate([
            'admin_note'     => 'nullable|string|max:1000',
            'approved_qty'   => 'required|array',
            'approved_qty.*' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($return, $request) {
            foreach ($return->items as $item) {
                $approvedQty = min((int) ($request->approved_qty[$item->id] ?? 0), $item->quantity_requested);
                $item->update(['quantity_approved' => $approvedQty]);

                if ($approvedQty > 0 && $item->product) {
                    $product = $item->product;
                    $before  = $product->stock;
                    $after   = $before + $approvedQty;
                    $product->increment('stock', $approvedQty);

                    StockAdjustment::create([
                        'product_id'   => $product->id,
                        'order_id'     => $return->order_id,
                        'type'         => 'return_in',
                        'quantity'     => $approvedQty,
                        'stock_before' => $before,
                        'stock_after'  => $after,
                        'reference'    => $return->return_number,
                        'reason'       => 'Customer return approved',
                        'adjusted_by'  => auth()->id(),
                    ]);
                }
            }

            $return->update([
                'status'       => 'approved',
                'admin_note'   => $request->admin_note,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);
        });

        $this->notifyReturnStatus($return);

        return back()->with('success', 'Return approved and stock updated.');
    }

    public function reject(Request $request, int $id)
    {
        $return = ProductReturn::findOrFail($id);

        if ($return->status !== 'pending') {
            return back()->with('error', 'Return is not in pending state.');
        }

        $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        $return->update([
            'status'       => 'rejected',
            'admin_note'   => $request->admin_note,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $this->notifyReturnStatus($return);

        return back()->with('success', 'Return request rejected.');
    }

    private function notifyReturnStatus(ProductReturn $return): void
    {
        if (! $return->user) {
            return;
        }

        NotificationDispatcher::customer('return_status_changed', $return->user, [
            'customer' => $return->user->name,
            'return_number' => $return->return_number,
            'order_number' => $return->order?->order_number,
            'status' => $return->status,
        ]);
    }
}
