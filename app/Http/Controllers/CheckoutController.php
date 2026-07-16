<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class CheckoutController extends Controller
{
    public function buyNow(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock.');
        }

        session(['buy_now' => [
            'product_id' => $product->id,
            'quantity' => (int) $request->quantity,
        ]]);

        return redirect()->route('checkout.index');
    }

    private function resolveCheckoutItems()
    {
        if ($buyNow = session('buy_now')) {
            $product = Product::find($buyNow['product_id']);

            if (! $product || $product->stock < $buyNow['quantity']) {
                session()->forget('buy_now');

                return collect();
            }

            $item = new Cart(['product_id' => $product->id, 'quantity' => $buyNow['quantity']]);
            $item->setRelation('product', $product);

            return collect([$item]);
        }

        return Cart::where('user_id', auth()->id())->with('product')->get();
    }

    public function index()
    {
        $cartItems = $this->resolveCheckoutItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum('subtotal');
        $coupon = session('coupon');
        $discount = $this->computeDiscount($subtotal, $coupon);
        $shipping = 60;
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $addresses = auth()->user()->addresses;

        // Per-method charges so Alpine.js can show live total
        $methodCharges = $paymentMethods->mapWithKeys(fn ($m) => [
            $m->slug => $m->calculateCharge($subtotal - $discount + $shipping),
        ]);

        $base = $subtotal - $discount + $shipping;
        $total = $base; // updated client-side per selected method

        return view('checkout.index', compact(
            'cartItems', 'subtotal', 'discount', 'shipping', 'total',
            'coupon', 'addresses', 'paymentMethods', 'methodCharges', 'base'
        ));
    }

    public function store(Request $request)
    {
        // Rate-limit: 10 checkout attempts per minute per user
        $key = 'checkout:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['error' => 'Too many requests. Please wait a moment.']);
        }
        RateLimiter::hit($key, 60);

        // Validate payment method against DB — never trust hardcoded ENUM
        $activeMethodSlugs = PaymentMethod::where('is_active', true)->pluck('slug')->implode(',');

        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_zip' => 'nullable|string|max:20',
            'payment_method' => 'required|exists:payment_methods,slug',
            // Mobile banking fields validated conditionally below
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:20',
        ]);

        $paymentMethod = PaymentMethod::where('slug', $request->payment_method)
            ->where('is_active', true)
            ->firstOrFail();

        // Require TXN ID for mobile banking / bank transfer
        if ($paymentMethod->requiresVerification()) {
            $request->validate([
                'transaction_id' => 'required|string|min:6|max:100',
                'sender_number' => 'required|string|max:20',
            ]);
        }

        $isBuyNow = session()->has('buy_now');
        $cartItems = $this->resolveCheckoutItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Server-side amount calculation — never take totals from the request
        $subtotal = $cartItems->sum('subtotal');
        $coupon = session('coupon');
        $discount = 0;
        $couponCode = null;

        if ($coupon) {
            $couponModel = Coupon::where('code', $coupon)->first();
            if ($couponModel && $couponModel->isValid()) {
                $discount = $couponModel->calculateDiscount($subtotal);
                $couponCode = $couponModel->code;
                $couponModel->increment('used_count');
            }
        }

        $shipping = 60;
        $base = $subtotal - $discount + $shipping;
        $paymentCharge = $paymentMethod->calculateCharge($base);
        $total = $base + $paymentCharge;

        // Enforce method amount limits
        if ($paymentMethod->min_amount && $total < $paymentMethod->min_amount) {
            return back()->withErrors(['payment_method' => "Minimum order amount for {$paymentMethod->name} is ৳{$paymentMethod->min_amount}."]);
        }
        if ($paymentMethod->max_amount && $total > $paymentMethod->max_amount) {
            return back()->withErrors(['payment_method' => "Maximum order amount for {$paymentMethod->name} is ৳{$paymentMethod->max_amount}."]);
        }

        $order = DB::transaction(function () use (
            $request, $cartItems, $subtotal, $discount, $shipping,
            $paymentCharge, $total, $couponCode, $paymentMethod, $isBuyNow
        ) {
            // Determine initial payment status
            $paymentStatus = match ($paymentMethod->type) {
                'cod' => 'pending',
                'mobile_banking', 'bank_transfer' => 'pending',
                'card' => 'pending',
                default => 'pending',
            };

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'tax' => 0,
                'total' => $total,
                'coupon_code' => $couponCode,
                'payment_method' => $paymentMethod->slug,
                'payment_charge' => $paymentCharge,
                'payment_status' => $paymentStatus,
                'shipping_name' => $request->shipping_name,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zip' => $request->shipping_zip,
                'shipping_country' => $request->shipping_country ?? 'Bangladesh',
                'notes' => $request->notes,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'price' => $item->product->effective_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]);
                $item->product->decrement('stock', $item->quantity);
            }

            // Create payment record
            $initialStatus = $paymentMethod->requiresVerification()
                ? 'pending_verification'
                : ($paymentMethod->type === 'cod' ? 'pending' : 'pending');

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'payment_method_slug' => $paymentMethod->slug,
                'payment_method_name' => $paymentMethod->name,
                'amount' => $total,
                'charge' => $paymentCharge,
                'status' => $initialStatus,
                'transaction_id' => $request->transaction_id,
                'sender_number' => $request->sender_number,
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            ]);

            // Link payment to order
            $order->update(['payment_id' => $payment->id]);

            if ($isBuyNow) {
                session()->forget('buy_now');
            } else {
                Cart::where('user_id', auth()->id())->delete();
            }

            return $order;
        });

        session()->forget('coupon');

        // Fire order_placed notifications for customer + admin
        $user = auth()->user();
        NotificationDispatcher::customer('order_placed', $user, [
            'order_number' => $order->order_number,
            'total' => '৳'.number_format($order->total, 2),
            'url' => route('orders.show', $order),
        ]);
        NotificationDispatcher::admin('new_order', [
            'order_number' => $order->order_number,
            'customer' => $user->name,
            'total' => '৳'.number_format($order->total, 2),
        ]);

        return redirect()->route('checkout.success', $order->id);
    }

    public function success(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        $order->load('payment');

        return view('checkout.success', compact('order'));
    }

    private function computeDiscount(float $subtotal, ?string $coupon): float
    {
        if (! $coupon) {
            return 0;
        }
        $model = Coupon::where('code', $coupon)->first();

        return ($model && $model->isValid()) ? $model->calculateDiscount($subtotal) : 0;
    }
}
