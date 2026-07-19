<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

        if (auth()->check()) {
            return Cart::where('user_id', auth()->id())->with('product')->get();
        }

        return Cart::where('session_id', session()->getId())->with('product')->get();
    }

    private function resolveCartCount()
    {
        if (auth()->check()) {
            return Cart::where('user_id', auth()->id())->count();
        }
        return Cart::where('session_id', session()->getId())->count();
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
        $addresses = auth()->check() ? auth()->user()->addresses : collect();

        $methodCharges = $paymentMethods->mapWithKeys(fn ($m) => [
            $m->slug => $m->calculateCharge($subtotal - $discount + $shipping),
        ]);

        $base = $subtotal - $discount + $shipping;
        $total = $base;

        return view('checkout.index', compact(
            'cartItems', 'subtotal', 'discount', 'shipping', 'total',
            'coupon', 'addresses', 'paymentMethods', 'methodCharges', 'base'
        ));
    }

    public function store(Request $request)
    {
        $guestIdentifier = auth()->check() ? 'checkout:'.auth()->id() : 'checkout:'.session()->getId();
        if (RateLimiter::tooManyAttempts($guestIdentifier, 10)) {
            return back()->withErrors(['error' => 'Too many requests. Please wait a moment.']);
        }
        RateLimiter::hit($guestIdentifier, 60);

        $activeMethodSlugs = PaymentMethod::where('is_active', true)->pluck('slug')->implode(',');

        $validationRules = [
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_zip' => 'nullable|string|max:20',
            'shipping_email' => 'nullable|email|max:255',
            'payment_method' => 'required|exists:payment_methods,slug',
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:20',
        ];

        $request->validate($validationRules);

        $paymentMethod = PaymentMethod::where('slug', $request->payment_method)
            ->where('is_active', true)
            ->firstOrFail();

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

        if ($paymentMethod->min_amount && $total < $paymentMethod->min_amount) {
            return back()->withErrors(['payment_method' => "Minimum order amount for {$paymentMethod->name} is ৳{$paymentMethod->min_amount}."]);
        }
        if ($paymentMethod->max_amount && $total > $paymentMethod->max_amount) {
            return back()->withErrors(['payment_method' => "Maximum order amount for {$paymentMethod->name} is ৳{$paymentMethod->max_amount}."]);
        }

        // ── Auto-create / find account by phone ──────────────────────────────
        $accountCreated = false;
        if (! auth()->check()) {
            $phone = preg_replace('/[^0-9]/', '', $request->shipping_phone);
            $existingUser = User::where('phone', $phone)->first();

            if ($existingUser) {
                Auth::login($existingUser);
            } else {
                $user = User::create([
                    'name' => $request->shipping_name,
                    'phone' => $phone,
                    'email' => $request->shipping_email ?: null,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'customer',
                    'is_active' => true,
                ]);
                Auth::login($user);
                $accountCreated = true;
            }
        }

        $guestToken = null;

        $order = DB::transaction(function () use (
            $request, $cartItems, $subtotal, $discount, $shipping,
            $paymentCharge, $total, $couponCode, $paymentMethod, $isBuyNow,
            $guestToken
        ) {
            $paymentStatus = match ($paymentMethod->type) {
                'cod' => 'pending',
                default => 'pending',
            };

            $order = Order::create([
                'user_id' => auth()->id(),
                'guest_email' => null,
                'guest_token' => $guestToken,
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

            $initialStatus = $paymentMethod->requiresVerification()
                ? 'pending_verification'
                : 'pending';

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

            $order->update(['payment_id' => $payment->id]);

            if ($isBuyNow) {
                session()->forget('buy_now');
            } elseif (auth()->check()) {
                Cart::where('user_id', auth()->id())->delete();
            } else {
                Cart::where('session_id', session()->getId())->delete();
            }

            return $order;
        });

        session()->forget('coupon');

        $user = auth()->user();
        NotificationDispatcher::customer('order_placed', $user, [
            'order_number' => $order->order_number,
            'total' => '৳'.number_format($order->total, 2),
            'url' => route('orders.show', $order),
        ]);

        NotificationDispatcher::admin('new_order', [
            'order_number' => $order->order_number,
            'customer' => $request->shipping_name,
            'total' => '৳'.number_format($order->total, 2),
        ]);

        return redirect()->route('checkout.success', $order->id)
            ->with('account_created', $accountCreated);
    }

    public function success(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }
        $order->load('payment');

        $accountCreated = session('account_created') ?? false;

        return view('checkout.success', compact('order', 'accountCreated'));
    }

    public function guestTrack(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'token' => 'required|string',
        ]);

        $order = Order::where('order_number', $request->order_number)
            ->where('guest_token', $request->token)
            ->firstOrFail();

        $order->load('items.product', 'payment');

        return view('orders.guest-track', compact('order'));
    }

    public function guestTrackForm()
    {
        return view('orders.guest-track-form');
    }

    public function guestTrackLookup(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'shipping_email' => 'nullable|email',
        ]);

        $query = Order::where('order_number', $request->order_number);

        if ($request->filled('shipping_email')) {
            $email = $request->shipping_email;
            $query->where(function ($q) use ($email) {
                $q->where('guest_email', $email)
                  ->orWhereHas('user', function ($q2) use ($email) {
                      $q2->where('email', $email);
                  });
            });
        }

        $order = $query->first();

        if (!$order) {
            return back()->withErrors(['order_number' => 'No order found with this order number.']);
        }

        if ($order->guest_token) {
            return redirect()->route('guest.order.track', [
                'order_number' => $order->order_number,
                'token' => $order->guest_token,
            ]);
        }

        return redirect()->route('orders.show', $order);
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
