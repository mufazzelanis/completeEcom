<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The rich part of order emails: a headline and message per status, a progress tracker, the
 * items with pictures, totals, delivery address and payment, and the buttons. The admin-editable
 * template (Admin → Notifications → Templates) is the greeting/intro shown inside it, so the
 * wording stays editable while the layout is always complete.
 */
class OrderEmail
{
    /** status => [icon, colour, headline, subject line] */
    private const STATUS = [
        'placed'     => ['🛍️', null, 'Thank you for your order!', 'We received your order'],
        'pending'    => ['🕒', '#f59e0b', 'Your order is pending', 'Your order is pending'],
        'processing' => ['📦', '#2563eb', 'Your order is confirmed', 'Your order is confirmed'],
        'shipped'    => ['🚚', '#7c3aed', 'Your order is on its way!', 'Your order is on its way'],
        'delivered'  => ['🎉', '#059669', 'Your order has been delivered', 'Your order has been delivered'],
        'cancelled'  => ['✖️', '#dc2626', 'Your order has been cancelled', 'Your order has been cancelled'],
        'refunded'   => ['↩️', '#0891b2', 'Your refund has been processed', 'Your refund has been processed'],
        'admin_new'  => ['🔔', null, 'New order received', 'New order'],
    ];

    public const STEPS = ['Placed', 'Confirmed', 'Shipped', 'Delivered'];

    /** Which of the above an event is about. */
    public static function stage(string $eventType, Order $order): string
    {
        return match ($eventType) {
            'order_placed' => 'placed',
            'new_order' => 'admin_new',
            default => array_key_exists($order->status, self::STATUS) ? $order->status : 'pending',
        };
    }

    /** Template variables an order can always fill in — so {{customer}} etc. are never left blank. */
    public static function vars(string $eventType, Order $order, ?User $user, array $context = []): array
    {
        $stage = self::stage($eventType, $order);
        $name = trim((string) ($order->shipping_name ?: $user?->name)) ?: 'there';

        return [
            'customer' => $name,
            'first_name' => Str::of($name)->explode(' ')->first() ?: $name,
            'order_number' => $order->order_number,
            'total' => self::money($order->total),
            'url' => route('orders.show', $order),   // short — fits an SMS
            'track_url' => self::orderUrl($order),   // opens without logging in
            'status' => ucfirst($order->status),
            'new_status' => ucfirst($order->status),
            'status_title' => self::STATUS[$stage][2],
            'status_subject' => self::STATUS[$stage][3],
            'status_message' => self::message($stage, $order, $context),
            'phone' => (string) $order->shipping_phone,
            'order_date' => $order->created_at?->timezone(setting('timezone', 'Asia/Dhaka'))->format('d M Y, g:i A') ?? '',
            'item_count' => (string) $order->items()->sum('quantity'),
        ];
    }

    public static function message(string $stage, Order $order, array $context = []): string
    {
        $site = setting('site_name', 'our store');
        $paid = $order->payment_status === 'paid';

        return match ($stage) {
            'placed' => $order->is_preorder
                ? "Your pre-order with {$site} is booked! We've received your advance of " . self::money($order->advance_amount) . " (we'll verify the payment shortly). We'll ship it as soon as the new stock arrives — the remaining " . self::money($order->total - $order->advance_amount) . " is paid on delivery."
                : "Thank you for shopping with {$site}! We've received your order and our team will confirm it shortly. We'll keep you updated by email at every step.",
            'pending' => "Your order is pending confirmation. Our team will confirm it shortly.",
            'processing' => "Good news — your order is confirmed and our team is now packing it with care. We'll let you know as soon as it ships.",
            'shipped' => "Your order has been handed over to our delivery partner and is on its way to you. Please keep your phone reachable so the rider can contact you.",
            'delivered' => "Your order has been delivered. We hope you love it! If anything isn't right, just reply to this email — we're always happy to help.",
            'cancelled' => (!empty($context['by_customer'])
                    ? "As you requested, your order has been cancelled."
                    : "Your order has been cancelled. If you didn't expect this, or you'd like to order again, please get in touch — we're glad to help.")
                . ($paid ? ' The amount you paid will be refunded to your original payment method.' : ''),
            'refunded' => "We've processed the refund for your order. Depending on your payment method it can take a few working days to show in your account.",
            'admin_new' => "{$order->shipping_name} placed an order for " . self::money($order->total) . '.',
            default => '',
        };
    }

    /** The email body: the template's intro inside the full order layout. */
    public static function body(string $eventType, string $recipient, string $intro, Order $order): string
    {
        $order->loadMissing('items.product');
        $stage = self::stage($eventType, $order);
        [$icon, $color, $title] = self::STATUS[$stage];
        $isAdmin = $recipient === 'admin';

        return view('emails.partials.order', [
            'order' => $order,
            'stage' => $stage,
            'icon' => $icon,
            'color' => $color ?: email_accent_color(),
            'title' => $title,
            'intro' => $intro,
            'isAdmin' => $isAdmin,
            'step' => match ($order->status) { 'processing' => 1, 'shipped' => 2, 'delivered' => 3, default => 0 },
            'showTracker' => !$isAdmin && !in_array($order->status, ['cancelled', 'refunded'], true),
            'ctaUrl' => $isAdmin ? route('admin.orders.show', $order) : self::orderUrl($order),
            'ctaLabel' => $isAdmin ? 'Open order in admin' : match ($stage) {
                'delivered' => 'View order & leave a review',
                'cancelled', 'refunded' => 'View order details',
                default => 'Track your order',
            },
            'money' => fn ($v) => self::money($v),
        ])->render();
    }

    public static function preheader(string $eventType, Order $order): string
    {
        $stage = self::stage($eventType, $order);

        return Str::limit(self::STATUS[$stage][2] . ' — order ' . $order->order_number . ', ' . self::money($order->total) . '. ' . self::message($stage, $order), 140);
    }

    /**
     * Link that opens this order without logging in (the public tracking page, by token) —
     * most customers check out as guests and never set a password.
     */
    public static function orderUrl(Order $order): string
    {
        if (!$order->guest_token) {
            $order->forceFill(['guest_token' => Str::random(64)])->saveQuietly();
        }

        return route('guest.order.track', ['order_number' => $order->order_number, 'token' => $order->guest_token]);
    }

    public static function money($amount): string
    {
        $amount = (float) $amount;

        return '৳' . number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
    }

    public static function paymentLabel(?string $method): string
    {
        return match (strtolower((string) $method)) {
            'cod', 'cash_on_delivery' => 'Cash on Delivery',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
            'sslcommerz', 'card' => 'Card / Online',
            '' => '—',
            default => Str::headline((string) $method),
        };
    }
}
