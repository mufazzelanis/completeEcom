<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function create(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        if ($order->status !== 'delivered') {
            return back()->with('error', 'Returns are only available for delivered orders.');
        }
        if ($order->returns()->where('status', '!=', 'rejected')->exists()) {
            return back()->with('error', 'A return request already exists for this order.');
        }

        $order->load('items');
        return view('orders.return', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        if ($order->status !== 'delivered') {
            abort(403);
        }
        if ($order->returns()->where('status', '!=', 'rejected')->exists()) {
            return back()->with('error', 'A return request already exists for this order.');
        }

        $request->validate([
            'reason'         => 'required|string|max:2000',
            'refund_type'    => 'required|in:refund,exchange,store_credit',
            'items'          => 'required|array|min:1',
            'items.*.qty'    => 'required|integer|min:0',
        ]);

        // At least one item must have qty > 0
        $hasItems = collect($request->items)->some(fn($i) => ($i['qty'] ?? 0) > 0);
        if (!$hasItems) {
            return back()->withInput()->with('error', 'Select at least one item to return.');
        }

        $productReturn = ProductReturn::create([
            'order_id'    => $order->id,
            'user_id'     => auth()->id(),
            'return_number' => ProductReturn::generateNumber(),
            'status'      => 'pending',
            'refund_type' => $request->refund_type,
            'reason'      => $request->reason,
        ]);

        foreach ($order->items as $orderItem) {
            $reqQty = (int) ($request->items[$orderItem->id]['qty'] ?? 0);
            if ($reqQty <= 0) continue;
            $qty = min($reqQty, $orderItem->quantity);

            ReturnItem::create([
                'return_id'          => $productReturn->id,
                'product_id'         => $orderItem->product_id,
                'order_item_id'      => $orderItem->id,
                'product_name'       => $orderItem->product_name,
                'quantity_requested' => $qty,
                'quantity_approved'  => 0,
            ]);
        }

        NotificationDispatcher::admin('new_return', [
            'customer' => auth()->user()->name,
            'order_number' => $order->order_number,
            'reason' => $request->reason,
        ]);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Return request submitted. We\'ll review and respond within 2–3 business days.');
    }
}
