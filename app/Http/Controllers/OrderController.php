<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $query = Order::where('user_id', auth()->id())->with('items.product');
        if ($status = request('status')) {
            $query->where('status', $status);
        }
        $orders = $query->latest()->paginate(10);
        return view('account.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if (auth()->check()) {
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $order->load('items.product');
        $existingReturn = $order->returns()->where('status', '!=', 'rejected')->first();
        return view('orders.show', compact('order', 'existingReturn'));
    }

    public function cancel(Order $order)
    {
        if (auth()->check()) {
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }
        } else {
            abort(403);
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'Order cancelled successfully.');
    }
}
