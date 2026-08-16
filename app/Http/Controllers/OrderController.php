<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Services\ActivityLogger;
use App\Services\OrderStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        ActivityLogger::log('order.view', "Viewed order #{$order->order_number}", $order);
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
        OrderStockService::restoreIfNeeded($order, 'cancelled', auth()->id());

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * Delivers a digital product's file for an order the customer owns. Every
     * order has a user_id (guest checkout auto-creates and logs the customer
     * in — see CheckoutController::store()), so ownership alone is enough;
     * there's no separate guest-token path needed here.
     */
    public function download(Order $order, OrderItem $item)
    {
        if (! auth()->check() || $order->user_id !== auth()->id()) {
            abort(403);
        }
        abort_unless($item->order_id === $order->id, 404);

        $item->loadMissing('product', 'combination');
        $product = $item->product;
        abort_unless($product && $product->isDigital() && $product->download_file, 404);

        if (in_array($order->status, ['cancelled', 'refunded'])) {
            return back()->with('error', 'This order was cancelled — the download is no longer available.');
        }

        $paymentMethod = PaymentMethod::where('slug', $order->payment_method)->first();
        if ($paymentMethod && $paymentMethod->requiresVerification() && $order->payment_status !== 'paid') {
            return back()->with('error', 'Your payment is still being verified. The download will unlock once it\'s confirmed.');
        }

        if ($item->download_expires_at && now()->gt($item->download_expires_at)) {
            return back()->with('error', 'The download window for this item has expired.');
        }

        if (! Storage::disk('private')->exists($product->download_file)) {
            return back()->with('error', 'File not found. Please contact support.');
        }

        $item->increment('download_count');
        $item->update(['last_downloaded_at' => now()]);

        ActivityLogger::log('order.download', "Downloaded \"{$product->name}\" from order #{$order->order_number}", $product);

        $extension = pathinfo($product->download_file, PATHINFO_EXTENSION);
        $filename = $product->slug . ($extension ? '.' . $extension : '');

        return Storage::disk('private')->download($product->download_file, $filename);
    }
}
