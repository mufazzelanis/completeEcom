<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductRecommendation;
use App\Models\PromoCode;
use App\Models\Setting;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getCartQuery()
    {
        if (auth()->check()) {
            return Cart::where('user_id', auth()->id());
        }

        return Cart::where('session_id', session()->getId());
    }

    private function currentSubtotal(): float
    {
        if ($buyNow = session('buy_now')) {
            $product = Product::find($buyNow['product_id']);
            if ($product) {
                return $product->final_price * $buyNow['quantity'];
            }
        }

        return $this->getCartQuery()->with('product')->get()->sum('subtotal');
    }

    public function index()
    {
        session()->forget('buy_now');

        $cartItems = $this->getCartQuery()->with('product')->get();
        $subtotal = $cartItems->sum('subtotal');
        $coupon = session('coupon');
        $promoCode = session('promo_code');
        $discount = 0;
        $appliedCode = null;

        if ($coupon) {
            $couponModel = Coupon::where('code', $coupon)->first();
            if ($couponModel && $couponModel->isValid()) {
                $discount = $couponModel->calculateDiscount($subtotal);
                $appliedCode = $coupon;
            } else {
                session()->forget('coupon');
            }
        } elseif ($promoCode) {
            $promoModel = PromoCode::where('code', $promoCode)->first();
            if ($promoModel && $promoModel->isValid()) {
                $discount = $promoModel->batch->calculateDiscount($subtotal);
                $appliedCode = $promoCode;
            } else {
                session()->forget('promo_code');
            }
        }

        $coupon = $appliedCode;

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

        $shipping = $subtotal > 0 ? 60 : 0;
        $total = $subtotal - $discount + $shipping;

        $cartProductIds = $cartItems->pluck('product_id');
        $crossSellProducts = ProductRecommendation::whereIn('product_id', $cartProductIds)
            ->where('type', 'cross_sell')
            ->whereNotIn('recommended_product_id', $cartProductIds)
            ->with('recommended.activeFlashSaleProduct')
            ->orderBy('sort_order')
            ->get()
            ->pluck('recommended')
            ->filter(fn ($p) => $p && $p->is_active)
            ->unique('id')
            ->take(4);

        return view('cart.index', compact(
            'cartItems', 'subtotal', 'discount', 'shipping', 'total', 'coupon', 'crossSellProducts',
            'pointsBalance', 'pointsRedeemed', 'pointsDiscount'
        ));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->available_stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock.');
        }

        session()->forget('buy_now');

        $cartQuery = $this->getCartQuery()->where('product_id', $product->id);
        $cartItem = $cartQuery->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'session_id' => auth()->check() ? null : session()->getId(),
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        return back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request, Cart $cart)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $cart->update(['quantity' => $request->quantity]);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Cart $cart)
    {
        $cart->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = strtoupper($request->code);
        $subtotal = $this->currentSubtotal();

        $coupon = Coupon::where('code', $code)->first();
        if ($coupon && $coupon->isValid()) {
            if ($subtotal < $coupon->min_order_amount) {
                return back()->with('error', 'This coupon requires a minimum order of ৳' . number_format((float) $coupon->min_order_amount) . '.');
            }

            session(['coupon' => $coupon->code]);
            session()->forget('promo_code');

            return back()->with('success', 'Coupon applied successfully!');
        }

        $promoCode = PromoCode::where('code', $code)->first();
        if ($promoCode && $promoCode->isValid()) {
            if ($subtotal < $promoCode->batch->min_order_amount) {
                return back()->with('error', 'This promo code requires a minimum order of ৳' . number_format((float) $promoCode->batch->min_order_amount) . '.');
            }

            session(['promo_code' => $promoCode->code]);
            session()->forget('coupon');

            return back()->with('success', 'Promo code applied successfully!');
        }

        return back()->with('error', 'Invalid or expired code.');
    }

    public function removeCoupon()
    {
        session()->forget(['coupon', 'promo_code']);

        return back()->with('success', 'Discount code removed.');
    }

    public function applyPoints(Request $request)
    {
        $request->validate(['points' => 'required|integer|min:1']);

        $user = auth()->user();
        $rate = (float) Setting::get('points.redeem_rate', 1);
        $subtotal = $this->currentSubtotal();
        $maxUsable = min($user->points_balance, (int) floor($subtotal / max($rate, 0.01)));

        if ($maxUsable <= 0) {
            return back()->with('error', 'You have no points available to use on this order.');
        }

        $points = min((int) $request->points, $maxUsable);
        session(['points_redeemed' => $points]);

        return back()->with('success', "{$points} points applied!");
    }

    public function removePoints()
    {
        session()->forget('points_redeemed');

        return back()->with('success', 'Points removed.');
    }
}
