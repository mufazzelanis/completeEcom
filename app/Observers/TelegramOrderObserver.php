<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Telegram\TelegramNotifier;

/**
 * Separate from OrderAlertObserver (the in-app/browser-push admin bell) on purpose, even
 * though the trigger conditions mirror it closely — a different notification channel with
 * its own on/off switch (Settings -> Notifications) shouldn't be bolted onto a class whose
 * whole job is the bell/toast system. afterCommit = true for the same reason as that one:
 * checkout/landing-page orders are created inside a DB::transaction that also writes order
 * items and decrements stock, so alerting on the raw "created" event would fire for an order
 * that then rolls back.
 */
class TelegramOrderObserver
{
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        // An admin typing a phone order in by hand already knows about it.
        if ($order->created_by || $order->source === 'phone') {
            return;
        }

        $message = "🛒 <b>New Order</b> — {$order->order_number}\n"
            . 'Customer: ' . e($order->shipping_name ?: 'Guest') . "\n"
            . 'Total: ৳' . number_format((float) $order->total) . "\n"
            . 'Payment: ' . $this->paymentStatusLabel($order->payment_status) . ' (' . strtoupper((string) $order->payment_method) . ')';

        TelegramNotifier::send($message, 'order_placed');
    }

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('payment_status')) {
            return;
        }

        $message = "💳 <b>Payment Updated</b> — {$order->order_number}\n"
            . 'Status: ' . $this->paymentStatusLabel($order->payment_status);

        TelegramNotifier::send($message, 'payment_status_changed');
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => '✅ Paid',
            'pending' => '🕒 Pending',
            'failed' => '❌ Failed',
            'refunded' => '↩️ Refunded',
            default => ucfirst($status),
        };
    }
}
