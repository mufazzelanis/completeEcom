<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\ReferralCode;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\ReferralService;
use App\Services\ShippingCalculator;
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

        if ($product->available_stock < $request->quantity) {
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

            if (! $product || $product->available_stock < $buyNow['quantity']) {
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

    /**
     * Defaults preserve the exact behavior/text this app had before these fields became
     * admin-configurable: name/address/city/phone were hardcoded required, state/zip/email/notes
     * were hardcoded optional. Country has no "mode" of its own (always shown filled-in — see
     * below) and Name's mode can't be changed (an order always needs a name to ship to).
     */
    private const CHECKOUT_FIELD_DEFAULTS = [
        'name'    => ['label' => 'Full Name', 'placeholder' => '', 'mode' => 'required'],
        'phone'   => ['label' => 'Phone', 'placeholder' => '01XXXXXXXXX', 'mode' => 'required'],
        'address' => ['label' => 'Address', 'placeholder' => 'Street address, house number, area...', 'mode' => 'required'],
        'city'    => ['label' => 'City', 'placeholder' => 'Dhaka', 'mode' => 'required'],
        'state'   => ['label' => 'District', 'placeholder' => 'Dhaka', 'mode' => 'optional'],
        'zip'     => ['label' => 'ZIP Code', 'placeholder' => '1207', 'mode' => 'optional'],
        'country' => ['label' => 'Country', 'placeholder' => '', 'mode' => 'optional'],
        'email'   => ['label' => 'Email', 'placeholder' => '', 'mode' => 'optional'],
        'notes'   => ['label' => 'Order Notes', 'placeholder' => 'Any special instructions...', 'mode' => 'optional'],
    ];

    private function resolveCheckoutFields(): array
    {
        $fields = [];

        foreach (self::CHECKOUT_FIELD_DEFAULTS as $key => $default) {
            $fields[$key] = [
                'label' => Setting::get("checkout_label_{$key}", $default['label']),
                'placeholder' => Setting::get("checkout_placeholder_{$key}", $default['placeholder']),
                'mode' => Setting::get("checkout_field_{$key}", $default['mode']),
            ];
        }

        return $fields;
    }

    public function index()
    {
        $cartItems = $this->resolveCheckoutItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum('subtotal');
        $coupon = session('coupon');
        $promoCode = session('promo_code');
        $discount = $this->computeDiscount($subtotal, $coupon, $promoCode);

        $pointsBalance = auth()->check() ? auth()->user()->points_balance : 0;
        $pointsRedeemed = 0;
        $pointsDiscount = 0;
        if (auth()->check() && session('points_redeemed')) {
            $rate = (float) Setting::get('points.redeem_rate', 1);
            $remaining = max(0, $subtotal - $discount);
            $pointsRedeemed = min((int) session('points_redeemed'), $pointsBalance, (int) floor($remaining / max($rate, 0.01)));
            $pointsDiscount = $pointsRedeemed * $rate;
            $discount += $pointsDiscount;
        }

        $usesZones = ShippingCalculator::usesZones();
        $shippingByZone = [
            'dhaka'         => ShippingCalculator::calculate($subtotal, ShippingCalculator::ZONE_DHAKA),
            'outside_dhaka' => ShippingCalculator::calculate($subtotal, ShippingCalculator::ZONE_OUTSIDE_DHAKA),
        ];
        $defaultZone = old('shipping_zone', 'dhaka');
        $shipping = $usesZones ? $shippingByZone[$defaultZone] : ShippingCalculator::calculate($subtotal);

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('sort_order')->get();
        $addresses = auth()->check() ? auth()->user()->addresses : collect();

        $methodCharges = $paymentMethods->mapWithKeys(fn ($m) => [
            $m->slug => $m->calculateCharge($subtotal - $discount + $shipping),
        ]);
        $methodChargesByZone = $usesZones ? [
            'dhaka'         => $paymentMethods->mapWithKeys(fn ($m) => [$m->slug => $m->calculateCharge($subtotal - $discount + $shippingByZone['dhaka'])]),
            'outside_dhaka' => $paymentMethods->mapWithKeys(fn ($m) => [$m->slug => $m->calculateCharge($subtotal - $discount + $shippingByZone['outside_dhaka'])]),
        ] : null;

        $base = $subtotal - $discount + $shipping;
        $total = $base;

        $checkoutFields = $this->resolveCheckoutFields();

        return view('checkout.index', compact(
            'cartItems', 'subtotal', 'discount', 'shipping', 'total',
            'coupon', 'promoCode', 'addresses', 'paymentMethods', 'methodCharges', 'base',
            'usesZones', 'shippingByZone', 'defaultZone', 'methodChargesByZone',
            'pointsBalance', 'pointsRedeemed', 'pointsDiscount', 'checkoutFields'
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

        $checkoutFields = $this->resolveCheckoutFields();
        $fieldRule = fn (string $mode) => $mode === 'required' ? 'required' : 'nullable';
        // Guests are identified/created by phone number at checkout (users.phone is unique),
        // so phone can never actually be optional/hidden for a guest regardless of the setting —
        // the admin setting only takes effect for already-authenticated customers.
        $phoneMode = auth()->check() ? $checkoutFields['phone']['mode'] : 'required';

        $validationRules = [
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => $fieldRule($phoneMode) . '|string|max:20',
            'shipping_address' => $fieldRule($checkoutFields['address']['mode']) . '|string|max:500',
            'shipping_city' => $fieldRule($checkoutFields['city']['mode']) . '|string|max:100',
            'shipping_state' => $fieldRule($checkoutFields['state']['mode']) . '|string|max:100',
            'shipping_zip' => $fieldRule($checkoutFields['zip']['mode']) . '|string|max:20',
            'shipping_country' => $fieldRule($checkoutFields['country']['mode']) . '|string|max:100',
            'shipping_email' => $fieldRule($checkoutFields['email']['mode']) . '|email|max:255',
            'notes' => $fieldRule($checkoutFields['notes']['mode']) . '|string|max:1000',
            'shipping_zone' => ShippingCalculator::usesZones() ? 'required|in:dhaka,outside_dhaka' : 'nullable|in:dhaka,outside_dhaka',
            'payment_method' => 'required|exists:payment_methods,slug',
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:20',
        ];

        $request->validate($validationRules);
        $request->merge(['shipping_phone' => normalize_digits($request->shipping_phone)]);

        // Defense in depth: never persist a value for a field the admin has hidden,
        // regardless of what a crafted request might submit.
        foreach ([
            'state' => 'shipping_state', 'zip' => 'shipping_zip', 'email' => 'shipping_email', 'notes' => 'notes',
            'city' => 'shipping_city', 'address' => 'shipping_address', 'country' => 'shipping_country',
        ] as $field => $inputKey) {
            if ($checkoutFields[$field]['mode'] === 'hidden') {
                $request->merge([$inputKey => null]);
            }
        }

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
        $promoCode = session('promo_code');
        $discount = 0;
        $couponCode = null;

        $couponId = null;
        $promoCodeId = null;
        if ($coupon) {
            $couponModel = Coupon::where('code', $coupon)->first();
            if ($couponModel && $couponModel->isValid()) {
                $discount = $couponModel->calculateDiscount($subtotal);
                $couponCode = $couponModel->code;
                $couponId = $couponModel->id;
            }
        } elseif ($promoCode) {
            $promoModel = PromoCode::where('code', $promoCode)->first();
            if ($promoModel && $promoModel->isValid()) {
                $discount = $promoModel->batch->calculateDiscount($subtotal);
                $couponCode = $promoModel->code;
                $promoCodeId = $promoModel->id;
            }
        }

        $pointsRedeemed = 0;
        $pointsDiscountValue = 0;
        if (auth()->check() && session('points_redeemed')) {
            $rate = (float) Setting::get('points.redeem_rate', 1);
            $remaining = max(0, $subtotal - $discount);
            $pointsRedeemed = min((int) session('points_redeemed'), auth()->user()->points_balance, (int) floor($remaining / max($rate, 0.01)));
            $pointsDiscountValue = $pointsRedeemed * $rate;
            $discount += $pointsDiscountValue;
        }

        $shippingZone = ShippingCalculator::usesZones() ? $request->shipping_zone : null;
        $shipping = ShippingCalculator::calculate($subtotal, $shippingZone);
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
            $phone = preg_replace('/[^0-9]/', '', normalize_digits($request->shipping_phone));
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

                if ($refCode = session('ref_code')) {
                    $referralCode = ReferralCode::where('code', $refCode)->where('user_id', '!=', $user->id)->first();
                    if ($referralCode) {
                        $user->update(['referred_by' => $referralCode->user_id]);
                        $referralCode->increment('total_uses');
                    }
                    session()->forget('ref_code');
                }

                Auth::login($user);
                $accountCreated = true;
            }
        }

        $guestToken = null;

        $order = DB::transaction(function () use (
            $request, $cartItems, $subtotal, $discount, $shipping, $shippingZone,
            $paymentCharge, $total, $couponCode, $couponId, $promoCodeId, $pointsRedeemed, $pointsDiscountValue, $paymentMethod, $isBuyNow,
            $guestToken
        ) {
            if ($couponId) {
                Coupon::where('id', $couponId)
                    ->where(fn($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                    ->increment('used_count');
            }

            if ($pointsRedeemed > 0) {
                auth()->user()->decrement('points_balance', $pointsRedeemed);
            }

            $paymentStatus = 'pending';

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
                'points_redeemed' => $pointsRedeemed,
                'points_discount_value' => $pointsDiscountValue,
                'coupon_code' => $couponCode,
                'payment_method' => $paymentMethod->slug,
                'payment_charge' => $paymentCharge,
                'payment_status' => $paymentStatus,
                'shipping_name' => $request->shipping_name,
                'shipping_phone' => $request->shipping_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_zone' => $shippingZone,
                'shipping_state' => $request->shipping_state,
                'shipping_zip' => $request->shipping_zip,
                'shipping_country' => $request->shipping_country ?? 'Bangladesh',
                'notes' => $request->notes,
            ]);

            ReferralService::maybeReward($order);

            if ($pointsRedeemed > 0) {
                PointTransaction::create([
                    'user_id' => auth()->id(),
                    'type' => 'redeemed',
                    'points' => -$pointsRedeemed,
                    'order_id' => $order->id,
                    'description' => "Redeemed {$pointsRedeemed} pts on order {$order->order_number}",
                ]);
            }

            if ($promoCodeId) {
                $promoCode = PromoCode::find($promoCodeId);
                $promoCode->update([
                    'user_id' => auth()->id(),
                    'order_id' => $order->id,
                    'used_at' => now(),
                ]);
                $promoCode->batch->increment('used_count');
            }

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'price' => $item->product->final_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]);
                if ($item->product->isBundle()) {
                    foreach ($item->product->bundleItems as $bundleItem) {
                        $before = $bundleItem->itemProduct->stock;
                        $bundleItem->itemProduct->decrement('stock', $bundleItem->quantity * $item->quantity);
                        $this->maybeNotifyLowStock($bundleItem->itemProduct, $before);
                    }
                } else {
                    $before = $item->product->stock;
                    $item->product->decrement('stock', $item->quantity);
                    $this->maybeNotifyLowStock($item->product, $before);
                }

                $flashProduct = $item->product->activeFlashSaleProduct;
                if ($flashProduct && $flashProduct->isAvailable()) {
                    $flashProduct->increment('sold_count');
                }
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

        session()->forget(['coupon', 'promo_code', 'points_redeemed']);

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
        $order->load('payment', 'items.product');

        $accountCreated = session('account_created') ?? false;

        // Fire the Purchase/conversion event only the first time this order's success
        // page is viewed — a session flag stops a page refresh or revisit from
        // reporting the same sale to GA/Meta multiple times.
        $trackedKey = 'purchase_tracked_' . $order->id;
        $shouldTrackPurchase = !session($trackedKey, false);
        if ($shouldTrackPurchase) {
            session([$trackedKey => true]);
        }

        return view('checkout.success', compact('order', 'accountCreated', 'shouldTrackPurchase'));
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

    private function computeDiscount(float $subtotal, ?string $coupon, ?string $promoCode = null): float
    {
        if ($coupon) {
            $model = Coupon::where('code', $coupon)->first();
            if ($model && $model->isValid()) {
                return $model->calculateDiscount($subtotal);
            }
        }

        if ($promoCode) {
            $model = PromoCode::where('code', $promoCode)->first();
            if ($model && $model->isValid()) {
                return $model->batch->calculateDiscount($subtotal);
            }
        }

        return 0;
    }

    private function maybeNotifyLowStock(Product $product, float $before): void
    {
        $threshold = $product->low_stock_threshold;
        $after = $product->stock;

        if ($before > $threshold && $after <= $threshold) {
            NotificationDispatcher::admin('low_stock', [
                'product_name' => $product->name,
                'sku' => $product->sku,
                'stock' => $after,
            ]);
        }
    }
}
