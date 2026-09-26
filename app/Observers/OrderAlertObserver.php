<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\AdminAlerts\AdminAlerts;

/**
 * Separate from OrderObserver on purpose: this one must run only AFTER the surrounding
 * DB transaction commits. Checkout and landing-page orders are created inside a
 * DB::transaction that also writes the order items and decrements stock — alerting on the
 * raw "created" event would buzz the owner's phone for an order that then rolls back
 * (and, even when it commits, would fire before the items exist to count).
 */
class OrderAlertObserver
{
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        // An admin typing an order in by hand for a phone customer already knows about it.
        if ($order->created_by || $order->source === 'phone') {
            return;
        }

        $itemCount = $order->items()->count();

        $payment = $order->payment_method === 'cod'
            ? 'Cash on Delivery'
            : strtoupper((string) $order->payment_method);

        $body = collect([
            $order->shipping_name ?: 'Customer',
            '৳' . number_format((float) $order->total),
            $itemCount > 0 ? $itemCount . ($itemCount === 1 ? ' item' : ' items') : null,
            $payment ?: null,
        ])->filter()->implode(' · ');

        AdminAlerts::notify(
            type: 'order',
            title: 'New order ' . $order->order_number,
            body: $body,
            url: route('admin.orders.show', $order),
            data: ['order_id' => $order->id],
        );
    }
}
