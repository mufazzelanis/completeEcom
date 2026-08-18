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

        $quantity  = max(1, (int) ($validated['quantity'] ?? 1));
        $unitPrice = (float) ($landingPage->effective_price ?? 0);
        $total     = round($unitPrice * $quantity, 2);

        $order = DB::transaction(function () use ($landingPage, $validated, $quantity, $unitPrice, $total, $request) {
            $order = Order::create([
                'landing_page_id'   => $landingPage->id,
                'order_number'      => Order::generateOrderNumber(),
                'status'            => 'pending',
                'subtotal'          => $total,
                'discount'          => 0,
                'shipping'          => 0,
                'tax'               => 0,
                'total'             => $total,
                // Landing pages are a single-product, high-conversion COD funnel by design —
                // no payment-method picker to keep the form as short as possible.
                'payment_method'    => 'cod',
                'payment_status'    => 'pending',
                'shipping_name'     => $validated['name'],
                'shipping_phone'    => $validated['phone'],
                'shipping_address'  => $validated['address'] ?? null,
                'landing_page_data' => $request->input('custom', []),
            ]);

            OrderItem::create([
                'order_id'    => $order->id,
                'product_id'  => $landingPage->product_id,
                'product_name' => $landingPage->product->name ?? $landingPage->title,
                'price'       => $unitPrice,
                'quantity'    => $quantity,
                'subtotal'    => $total,
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

        return redirect()->route('landing.show', $landingPage)->with('order_success', $order->order_number);
    }
}
