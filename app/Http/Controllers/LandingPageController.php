<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ActivityLogger;
use App\Services\Facebook\ConversionsApi;
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
        $pageViewToken = \App\Support\PageView::record('landing', $landingPage->id); // not on a hover-prefetch

        return view('landing.show', compact('landingPage', 'pageViewToken'));
    }

    public function order(Request $request, LandingPage $landingPage)
    {
        abort_if($landingPage->status !== 'published', 404);

        // Name, phone and full address on every order, like the store's own checkout — without
        // all three an order can't be confirmed by phone or delivered. (The page's old "collect /
        // require address" switches let orders through with no address at all.)
        $request->merge(array_map(fn ($v) => is_string($v) ? trim(preg_replace('/\s+/u', ' ', $v)) : $v, $request->only(['name', 'phone', 'address'])));
        $rules = [
            'name'     => ['required', 'string', 'min:2', 'max:255', function ($attr, $value, $fail) {
                if (preg_match_all('/\p{L}/u', (string) $value) < 2) {
                    $fail('আপনার নাম লিখুন (শুধু সংখ্যা বা চিহ্ন নয়)।');
                }
            }],
            'phone'    => ['required', 'string', 'max:30', function ($attr, $value, $fail) {
                // a number the courier and the confirmation call can reach, whatever the phone-check setting
                if (\App\Support\PhoneValidator::normalize($value) === null) {
                    $fail('সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন 01712345678)।');
                } elseif (\App\Support\PhoneValidator::mode() !== 'off' && ($problem = \App\Support\PhoneValidator::problem($value))) {
                    $fail('নম্বরটি সঠিক মনে হচ্ছে না — আপনার আসল চালু মোবাইল নম্বর দিন।');
                }
            }],
            'address'  => ['required', 'string', 'min:10', 'max:500', function ($attr, $value, $fail) {
                if (!\App\Support\AddressCheck::looksReal((string) $value, (string) request('phone'))) {
                    $fail(\App\Support\AddressCheck::MESSAGE);
                }
            }],
            // Capped at 5 per order — landing pages are a single-product impulse-buy funnel,
            // not a bulk ordering form; a higher quantity here is far more likely to be a
            // fat-fingered/bot submission than a genuine bulk buyer (who'd contact the shop).
            'quantity' => 'nullable|integer|min:1|max:5',
        ];
        $deliveryOptions = $landingPage->deliveryOptions(); // Settings → Shipping, the page's own zones, or none
        if ($deliveryOptions) {
            // a total can't be worked out without knowing which area applies
            $rules['delivery_zone'] = 'required|integer|min:0|max:' . (count($deliveryOptions) - 1);
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

        $validated = $request->validate($rules, [
            'name.required'    => 'আপনার নাম লিখুন।',
            'name.min'         => 'আপনার পুরো নাম লিখুন।',
            'phone.required'   => 'মোবাইল নম্বর দিন — অর্ডার নিশ্চিত করতে আমরা কল করবো।',
            'address.required' => 'ডেলিভারির জন্য সম্পূর্ণ ঠিকানা দিন।',
            'address.min'      => \App\Support\AddressCheck::MESSAGE,
            'address.max'      => 'ঠিকানা ৫০০ অক্ষরের মধ্যে লিখুন।',
            'delivery_zone.required' => 'ডেলিভারি এলাকা বেছে নিন।',
        ]);

        $quantity = max(1, (int) ($validated['quantity'] ?? 1));
        $phone = \App\Support\PhoneValidator::normalize($validated['phone']) ?? $validated['phone'];

        // Double-submit guard: a second tap on a slow mobile connection (or a refresh that
        // re-POSTs) used to create a duplicate order. The same phone ordering the same
        // quantity from the same page within 3 minutes is that same order — show its
        // thank-you page again instead of placing another one.
        $duplicate = Order::where('landing_page_id', $landingPage->id)
            ->where('shipping_phone', $phone)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->whereHas('items', fn ($q) => $q->where('quantity', $quantity))
            ->latest()
            ->first();
        if ($duplicate) {
            return redirect()->route('landing.show', $landingPage)->with([
                'order_success'       => $duplicate->order_number,
                'order_success_value' => (float) $duplicate->total,
            ]);
        }

        // Same stock check the regular storefront checkout does (CheckoutController) —
        // without it, a landing page under a burst of ad traffic could decrement a
        // product's stock into the negative with no warning to the customer at all.
        // Re-checked under a row lock inside the transaction below as well.
        if ($landingPage->product_id && $landingPage->product && $quantity > $landingPage->product->available_stock) {
            return back()->withInput()->withErrors(['quantity' => 'Sorry, only ' . max(0, $landingPage->product->available_stock) . ' left in stock.']);
        }

        $unitPrice = (float) ($landingPage->effective_price ?? 0);
        $subtotal = round($unitPrice * $quantity, 2);

        $zone = null;
        $shippingCharge = 0.0;
        if ($deliveryOptions && isset($validated['delivery_zone'])) {
            $zone = $deliveryOptions[(int) $validated['delivery_zone']] ?? null;
            // site settings: the store's own rate for the area, free-delivery threshold included
            $shippingCharge = $zone ? $landingPage->deliveryCharge((int) $validated['delivery_zone'], $subtotal) : 0.0;
        }
        $total = round($subtotal + $shippingCharge, 2);

        // The zone label rides along in landing_page_data purely for display in the admin's
        // Landing Orders screen — shipping_charge itself already landed on the order's own
        // `shipping` column, which is what actually drives the total. Underscore-prefixed key
        // (rather than plain 'delivery_zone') so it can never collide with an admin-defined
        // custom field slugging to that same key (e.g. one literally labeled "Delivery Zone").
        // Only the fields this page's form actually defines (and validated above) — never
        // whatever extra custom[...] keys a crafted request might add.
        $customData = [];
        foreach ($landingPage->order_form_fields ?? [] as $field) {
            $value = $validated['custom'][$field['key']] ?? null;
            if ($value !== null && $value !== '') {
                $customData[$field['key']] = $value;
            }
        }
        if ($zone) {
            $customData = ['_delivery_zone' => $zone['label']] + $customData;
        }

        $order = DB::transaction(function () use ($landingPage, $validated, $quantity, $unitPrice, $subtotal, $shippingCharge, $total, $customData, $phone, $zone) {
            // Row lock + re-check: two orders arriving at the same moment can both pass the
            // pre-check above against the same last unit; only one may take it.
            if ($landingPage->product_id) {
                $product = \App\Models\Product::whereKey($landingPage->product_id)->lockForUpdate()->first();
                if ($product && $quantity > $product->available_stock) {
                    return null;
                }
            }

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
                'shipping_phone'    => $phone,
                'shipping_address'  => $validated['address'] ?? null,
                // the store's own area (Inside / Outside Dhaka) when the page uses Settings → Shipping — couriers and reports read it
                'shipping_zone'     => in_array($zone['key'] ?? null, ['dhaka', 'outside_dhaka'], true) ? $zone['key'] : null,
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

        if (!$order) {
            return back()->withInput()->withErrors(['quantity' => 'Sorry, this product just went out of stock.']);
        }

        ActivityLogger::log(
            'order.place',
            "Placed order #{$order->order_number} via landing page \"{$landingPage->title}\" — {$order->shipping_name}, {$order->shipping_phone}",
            $order,
            ['phone' => $order->shipping_phone, 'name' => $order->shipping_name, 'total' => $order->total, 'landing_page' => $landingPage->title]
        );

        try {
            \App\Models\CheckoutLead::markRecovered($order, null); // they may have tried the normal checkout first
        } catch (\Throwable $e) {
            report($e);
        }

        NotificationDispatcher::admin('new_order', [
            'order_number' => $order->order_number,
            'customer'     => $order->shipping_name,
            'total'        => '৳' . number_format($order->total, 2),
            '_order'       => $order,
        ]);

        // Admin → Live Visitors funnel: the thank-you shows on this same URL (or on another site),
        // so the heartbeat can't see the order happen
        LiveVisitorController::markOrdered();

        // Flashed alongside order_success purely for the Purchase pixel event on the
        // thank-you page (see show.blade.php) — value/currency to report the conversion at,
        // plus whatever of the customer's name/phone can feed Meta Advanced Matching /
        // Google Enhanced Conversions (no email field on a landing order form, so that side
        // of pixel_advanced_matching_data() stays empty here). Same one-request flash
        // lifetime as order_success itself, so a refresh naturally stops re-reporting it.
        $tracking = pixel_advanced_matching_data($order->shipping_name, null, $order->shipping_phone);

        // Shared with the client-side fbq('track', 'Purchase', ..., {eventID}) call fired on
        // the redirected-to thank-you page (see show.blade.php) so Meta dedupes the pair into
        // one event. Fired here (not guarded by a session flag like the main checkout's
        // Purchase) because — unlike that GET success page, which can be revisited/refreshed —
        // this is the POST handler itself, which only ever runs once per order. Sent before the
        // optional redirect to an outside thank-you page below, which used to skip it entirely.
        $fbPurchaseEventId = 'purchase_' . $order->id;
        ConversionsApi::track(
            eventName: 'Purchase',
            eventId: $fbPurchaseEventId,
            customData: [
                'content_ids'  => [$landingPage->product_id ? (string) $landingPage->product_id : $landingPage->slug], // product ID, same as the pixel/catalog
                'content_type' => 'product',
                'value'        => (float) $order->total,
                'currency'     => setting('currency_code', 'BDT'),
            ],
            rawUserFields: ['name' => $order->shipping_name, 'phone' => $order->shipping_phone],
            pixelIdOverride: $landingPage->fb_pixel_id ?: null,
            accessTokenOverride: $landingPage->fb_capi_access_token ?: null,
        );

        if ($landingPage->thank_you_redirect_url) {
            return redirect()->away($landingPage->thank_you_redirect_url);
        }

        return redirect()->route('landing.show', $landingPage)->with([
            'order_success'         => $order->order_number,
            'order_success_value'   => (float) $order->total,
            'order_success_fb'      => $tracking['fb'],
            'order_success_google'  => $tracking['google'],
            'order_success_fb_eid'  => $fbPurchaseEventId,
        ]);
    }
}
