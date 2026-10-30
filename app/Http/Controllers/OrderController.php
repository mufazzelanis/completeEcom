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

        // Pending only — once an order moves to Processing, packing has likely already
        // started, so self-cancel stops there and it becomes a support conversation instead.
        if ($order->status !== 'pending') {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        $order->update(['status' => 'cancelled']);
        OrderStockService::restoreIfNeeded($order, 'cancelled', auth()->id());

        // confirmation for the customer (email / SMS / push per their preferences)
        \App\Services\Notifications\NotificationDispatcher::customer('order_status_changed', $order->user ?? auth()->user(), [
            'old_status' => 'Pending',
            '_order' => $order,
            '_context' => ['by_customer' => true],
        ]);

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * "Order again": puts this order's items back in the cart — same product and variant, as
     * many as are still in stock (up to the original quantity) — and opens the cart. Items no
     * longer sold, or out of stock, are skipped and named in the message.
     */
    public function reorder(Order $order)
    {
        abort_unless(auth()->check() && $order->user_id === auth()->id(), 403);
        $order->load('items.product');

        $added = 0;
        $skipped = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $combination = $item->product_variant_combination_id
                ? \App\Models\ProductVariantCombination::where('id', $item->product_variant_combination_id)->where('is_active', true)->first()
                : null;
            $available = $product?->marked_out_of_stock ? 0 : ($combination ? (int) $combination->stock : (int) ($product?->available_stock ?? 0));
            if (! $product || ! $product->is_active || ($product->isVariable() && ! $combination) || $available < 1) {
                $skipped[] = $item->product_name;
                continue;
            }

            $qty = min((int) $item->quantity, $available);
            $line = \App\Models\Cart::where('user_id', auth()->id())->where('product_id', $product->id)
                ->when($combination, fn ($q) => $q->where('product_variant_combination_id', $combination->id),
                    fn ($q) => $q->whereNull('product_variant_combination_id'))
                ->first();
            if ($line) {
                $line->update(['quantity' => max((int) $line->quantity, $qty)]);
            } else {
                \App\Models\Cart::create([
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                    'product_variant_combination_id' => $combination?->id,
                    'quantity' => $qty,
                ]);
            }
            $added++;
        }
        session()->forget('buy_now');
        ActivityLogger::log('cart.add', "Re-ordered items from #{$order->order_number}", $order, ['added' => $added]);

        if ($added === 0) {
            return back()->with('error', 'Sorry — none of these items are available right now.');
        }

        return redirect()->route('cart.index')->with(
            $skipped ? 'error' : 'success',
            $skipped
                ? "{$added} item(s) added to your cart. Not available right now: " . implode(', ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? '…' : '')
                : 'Items from your previous order are in your cart.'
        );
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
