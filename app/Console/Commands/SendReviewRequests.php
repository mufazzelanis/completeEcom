<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\SmsChannel;
use Illuminate\Console\Command;

/**
 * Asks customers to review what they bought, a few days after delivery (once they've actually
 * used it): an email when the order has an address, plus an SMS to the order's phone if that's
 * switched on — both with the order's short review link (Order::reviewUrl(), works without an
 * account). Customers with neither (phone-only orders while SMS is off) review from their
 * account's order page instead. Runs hourly in the daytime; each order is asked exactly once.
 *
 * Settings (Admin → Settings → Notifications → Review requests):
 *   review_requests_enabled   on/off
 *   review_request_delay_days days after delivery (default 3)
 *   review_request_sms_enabled also text the order's phone (default off — email only)
 *   review_request_sms        SMS text — {name} {order} {site} {link}
 *
 * Only orders whose delivery was recorded (delivered_at, stamped on the status change) are
 * considered, and only within two weeks of the planned send time — so turning this on, or
 * deploying it, never messages customers about old orders.
 */
class SendReviewRequests extends Command
{
    protected $signature = 'reviews:send-requests
        {--dry-run : List who would be asked, send nothing}
        {--limit=50 : Most orders to handle in one run}';

    protected $description = 'Send post-delivery review requests (email, optionally SMS) with each order\'s review link';

    public const DEFAULT_SMS = 'Hi {name}, thanks for shopping at {site}! How was your order {order}? Please rate it (1 min): {link}';

    public function handle(SmsChannel $sms, EmailChannel $email): int
    {
        if (setting('review_requests_enabled', '1') !== '1') {
            $this->line('Review requests are turned off (Settings → Notifications).');

            return self::SUCCESS;
        }

        $delay = max(0, (int) setting('review_request_delay_days', '3'));
        $orders = Order::with('user')
            ->whereIn('status', ['delivered', 'completed'])
            ->whereNull('review_requested_at')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subDays($delay))
            ->where('delivered_at', '>=', now()->subDays($delay + 14))
            ->orderBy('delivered_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $site = setting('site_name', config('app.name'));
        $template = trim((string) setting('review_request_sms', '')) ?: self::DEFAULT_SMS;
        $dry = (bool) $this->option('dry-run');
        $smsOn = setting('review_request_sms_enabled', '0') === '1';
        $asked = 0;

        foreach ($orders as $order) {
            $name = $this->firstName($order);
            $phone = trim((string) $order->shipping_phone);
            $address = $order->user?->email ?: $order->guest_email;
            if (!$address && !($smsOn && $phone !== '')) {
                // nothing to send it through — this customer reviews from their order page
                $this->line("{$order->order_number}  {$name}  (no email — skipped)");
                continue;
            }
            $this->line(sprintf('%s  %s  %s%s', $order->order_number, $name, $address ?: '-', $smsOn && $phone !== '' ? "  SMS {$phone}" : ''));
            $asked++;
            if ($dry) {
                continue;
            }

            $link = $order->reviewUrl();
            if ($smsOn && $phone !== '') {
                $sms->send($order->user_id, 'review_request', $phone,
                    strtr($template, ['{name}' => $name, '{order}' => $order->order_number, '{site}' => $site, '{link}' => $link]));
            }
            if ($address) {
                $email->send($order->user_id, 'review_request', $address, "How was your order {$order->order_number}?", $this->emailBody($name, $order, $site, $link));
            }
            $order->forceFill(['review_requested_at' => now()])->saveQuietly();
        }

        $this->info(($dry ? 'Would ask ' : 'Asked ') . $asked . ' order(s) for a review.');

        return self::SUCCESS;
    }

    private function firstName(Order $order): string
    {
        $parts = preg_split('/\s+/u', trim($order->user?->name ?: (string) $order->shipping_name), -1, PREG_SPLIT_NO_EMPTY);

        return $parts[0] ?? 'there';
    }

    private function emailBody(string $name, Order $order, string $site, string $link): string
    {
        $e = fn ($v) => e($v);
        $items = $order->items()->with('product')->get()->filter->product
            ->map(fn ($i) => '<li>' . $e($i->product->name) . '</li>')->implode('');

        return '<p>Hi ' . $e($name) . ',</p>'
            . '<p>Thank you for shopping at ' . $e($site) . '! We hope you\'re happy with your order <strong>' . $e($order->order_number) . '</strong>:</p>'
            . ($items ? '<ul>' . $items . '</ul>' : '')
            . '<p>Could you take a minute to rate it? Your review helps other customers choose with confidence.</p>'
            . '<p style="text-align:center;margin:24px 0"><a href="' . $e($link) . '" style="background:#c2410c;color:#ffffff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block">Rate my order ⭐</a></p>'
            . '<p style="color:#6b7280;font-size:13px">No login needed — the button opens a page with your products.</p>';
    }
}
