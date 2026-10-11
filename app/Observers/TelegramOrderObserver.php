<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\ShippingCalculator;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Support\Str;

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

        TelegramNotifier::send($this->newOrderMessage($order), 'order_placed', $this->copyButtons($order));
    }

    public function updated(Order $order): void
    {
        if (!$order->wasChanged('payment_status')) {
            return;
        }

        $message = "💳 <b>Payment Updated</b> — {$order->order_number}\n"
            . '🕒 ' . $this->localTime(now()) . "\n"
            . '👤 ' . e($order->shipping_name ?: 'Guest') . ($order->shipping_phone ? ' · 📞 ' . e($order->shipping_phone) : '') . "\n"
            . '💰 ' . $this->money($order->total) . ' · ' . $this->paymentStatusLabel($order->payment_status)
            . ($this->adminUrlButton($order) ? '' : "\n" . '🔗 <a href="' . e(route('admin.orders.show', $order)) . '">Open order</a>');

        TelegramNotifier::send($message, 'payment_status_changed', array_values(array_filter([
            [$this->copyButton('📋 Order No', $order->order_number)],
            $this->adminUrlButton($order) ? [$this->adminUrlButton($order)] : null,
        ])));
    }

    /**
     * Everything needed to act on an order straight from the phone — call the customer
     * (Telegram makes the number tappable), see where it ships, what to pack and what to
     * collect on delivery — without opening the admin panel. Every customer-typed value is
     * HTML-escaped (the message is sent with parse_mode=HTML).
     */
    private function newOrderMessage(Order $order): string
    {
        $order->loadMissing('items', 'landingPage');
        $lines = [];

        $lines[] = "🛒 <b>New Order</b> — <code>{$order->order_number}</code>";
        $lines[] = '🕒 ' . $this->localTime($order->created_at ?? now());
        $lines[] = '';

        // Customer + full delivery address
        // <code> = tap-to-copy in the Telegram apps, on top of the 📋 buttons below.
        $lines[] = '👤 <b><code>' . e($order->shipping_name ?: 'Guest') . '</code></b>';
        if ($order->shipping_phone) {
            $lines[] = '📞 <code>' . e($order->shipping_phone) . '</code>';
        }
        $address = $this->fullAddress($order);
        $lines[] = '📍 ' . ($address !== '' ? '<code>' . e($address) . '</code>' : '<i>No address given</i>');
        $zone = $order->landing_page_data['_delivery_zone']
            ?? ($order->shipping_zone ? ShippingCalculator::label($order->shipping_zone) : null);
        if ($zone) {
            $lines[] = '🚚 ' . e($zone);
        }
        $lines[] = '';

        // What to pack
        $lines[] = '📦 <b>Items</b>';
        foreach ($order->items->take(15) as $item) {
            $lines[] = '• ' . e(Str::limit($item->product_name, 70))
                . ($item->variant_label ? ' <i>(' . e($item->variant_label) . ')</i>' : '')
                . ' × ' . $item->quantity . ' — ' . $this->money($item->subtotal);
        }
        if ($order->items->count() > 15) {
            $lines[] = '• … +' . ($order->items->count() - 15) . ' more';
        }
        $lines[] = '';

        // What to collect
        $lines[] = 'Subtotal: ' . $this->money($order->subtotal);
        if ((float) $order->shipping > 0) {
            $lines[] = 'Delivery: ' . $this->money($order->shipping);
        }
        if ((float) $order->discount > 0) {
            $lines[] = 'Discount: −' . $this->money($order->discount) . ($order->coupon_code ? ' (' . e($order->coupon_code) . ')' : '');
        }
        $lines[] = '💰 <b>Total: <code>' . $this->money($order->total) . '</code></b>';
        $lines[] = '💳 ' . strtoupper((string) $order->payment_method) . ' · ' . $this->paymentStatusLabel($order->payment_status);
        $lines[] = '';

        // Where it came from + anything else the customer told us
        $lines[] = '🌐 ' . ($order->landingPage
            ? 'Landing page: ' . e($order->landingPage->title)
            : 'Website' . ($order->source && $order->source !== 'web' ? ' (' . e($order->source) . ')' : ''));
        foreach ($this->landingFields($order) as $label => $value) {
            $lines[] = '▫️ ' . e($label) . ': ' . e($value);
        }
        if (filled($order->notes)) {
            $lines[] = '📝 ' . e(Str::limit($order->notes, 300));
        }
        if ($order->is_fraud_flagged) {
            $lines[] = '⚠️ <b>Fraud check flagged this order</b>' . ($order->fraud_score !== null ? ' (score ' . e($order->fraud_score) . ')' : '');
        }
        if (!$this->adminUrlButton($order)) {
            $lines[] = '🔗 <a href="' . e(route('admin.orders.show', $order)) . '">Open in admin</a>';
        }
        $lines[] = '';
        $lines[] = '<i>Tap a highlighted value, or a 📋 button below, to copy it.</i>';

        return implode("\n", $lines);
    }

    /**
     * One-tap copy buttons under the message (Telegram's copy_text buttons) — each field on
     * its own button, plus "Copy all" with name / phone / address / COD amount as one block,
     * ready to paste into a courier booking form. The price is copied as a plain number (no
     * currency sign or thousands separator), which is what courier COD fields expect.
     */
    private function copyButtons(Order $order): array
    {
        $address = $this->fullAddress($order);
        $amount = rtrim(rtrim(number_format((float) $order->total, 2, '.', ''), '0'), '.');
        $all = collect([$order->shipping_name, $order->shipping_phone, $address, 'COD: ' . $amount])
            ->filter()->implode("\n");

        return array_values(array_filter([
            array_values(array_filter([
                $this->copyButton('📋 Name', $order->shipping_name),
                $this->copyButton('📋 Phone', $order->shipping_phone),
            ])),
            array_values(array_filter([$this->copyButton('📋 Full Address', $address)])),
            array_values(array_filter([
                $this->copyButton('📋 Order No', $order->order_number),
                $this->copyButton('📋 Price ' . $this->money($order->total), $amount),
            ])),
            [$this->copyButton('📋 Copy all (for courier)', $all)],
            array_values(array_filter([$this->adminUrlButton($order)])),
        ]));
    }

    private function copyButton(string $label, ?string $value): ?array
    {
        $value = trim((string) $value);

        // Telegram caps copy_text at 256 characters.
        return $value === '' ? null : ['text' => $label, 'copy_text' => ['text' => Str::limit($value, 253)]];
    }

    /** URL buttons must be public https links — a local http://localhost URL gets rejected. */
    private function adminUrlButton(Order $order): ?array
    {
        $url = route('admin.orders.show', $order);

        return str_starts_with($url, 'https://') ? ['text' => '🔗 Open in admin', 'url' => $url] : null;
    }

    private function fullAddress(Order $order): string
    {
        return collect([$order->shipping_address, $order->shipping_city, $order->shipping_state, $order->shipping_zip])
            ->map(fn ($part) => trim((string) $part))->filter()->unique()->implode(', ');
    }

    /** A landing page's own extra form fields (Size, Color…) with their labels. */
    private function landingFields(Order $order): array
    {
        $data = collect($order->landing_page_data ?? [])->except('_delivery_zone');
        if ($data->isEmpty()) {
            return [];
        }
        $fields = collect($order->landingPage?->order_form_fields ?? [])->keyBy('key');

        return $data->mapWithKeys(fn ($value, $key) => [
            ($fields[$key]['label'] ?? Str::headline($key)) => ($fields[$key]['type'] ?? null) === 'checkbox'
                ? (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No')
                : Str::limit((string) $value, 120),
        ])->all();
    }

    private function localTime($time): string
    {
        return $time->copy()->timezone(setting('timezone', 'Asia/Dhaka') ?: 'Asia/Dhaka')->format('d M Y, h:i A');
    }

    private function money($amount): string
    {
        return html_entity_decode(setting('currency_symbol', '৳')) . number_format((float) $amount, (int) setting('decimal_places', 0));
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
