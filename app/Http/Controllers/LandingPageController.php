<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ActivityLogger;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    public function show(LandingPage $landingPage)
    {
        // Draft pages 404 publicly (not just "not found styled differently") so an
        // unfinished/unpublished funnel can never leak before the admin is ready for it.
        abort_if($landingPage->status !== 'published', 404);

        $landingPage->load('product');
        $landingPage->increment('views_count');

        return view('landing.show', compact('landingPage'));
    }

    public function order(Request $request, LandingPage $landingPage)
    {
        abort_if($landingPage->status !== 'published', 404);

        $rules = [
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:30',
            'quantity' => 'nullable|integer|min:1|max:99',
        ];
        if ($landingPage->collect_address) {
            $rules['address'] = ($landingPage->require_address ? 'required' : 'nullable') . '|string|max:500';
        }
        if (filled($landingPage->delivery_zones)) {
            // Required once zones exist at all — a page with a shipping-charge picker can't
            // compute a total without knowing which zone applies.
            $rules['delivery_zone'] = 'required|integer|min:0|max:' . (count($landingPage->delivery_zones) - 1);
        }
        foreach ($landingPage->order_form_fields ?? [] as $field) {
            $rule = $field['required'] ? 'required' : 'nullable';
            $rule .= match ($field['type']) {
                'email'    => '|email|max:255',
                'number'   => '|numeric',
                'checkbox' => '|boolean',
                default    => '|string|max:1000',
            };
            $rules['custom.' . $field['key']] = $rule;
        }

        $validated = $request->validate($rules);

        $quantity = max(1, (int) ($validated['quantity'] ?? 1));

        // Same stock check the regular storefront checkout does (CheckoutController) —
        // without it, a landing page under a burst of ad traffic could decrement a
        // product's stock into the negative with no warning to the customer at all.
        if ($landingPage->product_id && $landingPage->product && $quantity > $landingPage->product->available_stock) {
            return back()->withInput()->withErrors(['quantity' => 'Sorry, only ' . max(0, $landingPage->product->available_stock) . ' left in stock.']);
        }

        $unitPrice = (float) ($landingPage->effective_price ?? 0);
        $subtotal = round($unitPrice * $quantity, 2);

        $zone = null;
        $shippingCharge = 0.0;
        if (filled($landingPage->delivery_zones) && isset($validated['delivery_zone'])) {
            $zone = $landingPage->delivery_zones[(int) $validated['delivery_zone']] ?? null;
            $shippingCharge = (float) ($zone['charge'] ?? 0);
        }
        $total = round($subtotal + $shippingCharge, 2);

        // The zone label rides along in landing_page_data purely for display in the admin's
        // Landing Orders screen — shipping_charge itself already landed on the order's own
        // `shipping` column, which is what actually drives the total. Underscore-prefixed key
        // (rather than plain 'delivery_zone') so it can never collide with an admin-defined
        // custom field slugging to that same key (e.g. one literally labeled "Delivery Zone").
        $customData = $request->input('custom', []);
        if ($zone) {
            $customData = ['_delivery_zone' => $zone['label']] + $customData;
        }

        $order = DB::transaction(function () use ($landingPage, $validated, $quantity, $unitPrice, $subtotal, $shippingCharge, $total, $customData) {
            $order = Order::create([
                'landing_page_id'   => $landingPage->id,
                'order_number'      => Order::generateOrderNumber(),
                'status'            => 'pending',
                'subtotal'          => $subtotal,
                'discount'          => 0,
                'shipping'          => $shippingCharge,
                'tax'               => 0,
                'total'             => $total,
                // Landing pages are a single-product, high-conversion COD funnel by design —
                // no payment-method picker to keep the form as short as possible.
                'payment_method'    => 'cod',
                'payment_status'    => 'pending',
                'shipping_name'     => $validated['name'],
                'shipping_phone'    => $validated['phone'],
                'shipping_address'  => $validated['address'] ?? null,
                'landing_page_data' => $customData,
            ]);

            OrderItem::create([
                'order_id'    => $order->id,
                'product_id'  => $landingPage->product_id,
                'product_name' => $landingPage->product?->name ?? $landingPage->title,
                'price'       => $unitPrice,
                'quantity'    => $quantity,
                'subtotal'    => $subtotal,
            ]);

            if ($landingPage->product_id) {
                $landingPage->product()->decrement('stock', $quantity);
            }

            return $order;
        });

        ActivityLogger::log(
            'order.place',
            "Placed order #{$order->order_number} via landing page \"{$landingPage->title}\" — {$order->shipping_name}, {$order->shipping_phone}",
            $order,
            ['phone' => $order->shipping_phone, 'name' => $order->shipping_name, 'total' => $order->total, 'landing_page' => $landingPage->title]
        );

        NotificationDispatcher::admin('new_order', [
            'order_number' => $order->order_number,
            'customer'     => $order->shipping_name,
            'total'        => '৳' . number_format($order->total, 2),
        ]);

        if ($landingPage->thank_you_redirect_url) {
            return redirect()->away($landingPage->thank_you_redirect_url);
        }

        // Flashed alongside order_success purely for the Purchase pixel event on the
        // thank-you page (see show.blade.php) — value/currency to report the conversion at,
        // plus whatever of the customer's name/phone can feed Meta Advanced Matching /
        // Google Enhanced Conversions (no email field on a landing order form, so that side
        // of pixel_advanced_matching_data() stays empty here). Same one-request flash
        // lifetime as order_success itself, so a refresh naturally stops re-reporting it.
        $tracking = pixel_advanced_matching_data($order->shipping_name, null, $order->shipping_phone);

        return redirect()->route('landing.show', $landingPage)->with([
            'order_success'        => $order->order_number,
            'order_success_value'  => (float) $order->total,
            'order_success_fb'     => $tracking['fb'],
            'order_success_google' => $tracking['google'],
        ]);
    }
}
