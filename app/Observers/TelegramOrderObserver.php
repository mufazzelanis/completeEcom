<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ShippingCalculator;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Watermark;
use Illuminate\Support\Str;

/**
 * Separate from OrderAlertObserver (the in-app/browser-push admin bell) on purpose, even
 * though the trigger conditions mirror it closely — a different notification channel with
 * its own on/off switch (Settings -> Notifications) shouldn't be bolted onto a class whose
 * whole job is the bell/toast system. afterCommit = true for the same reason as that one:
 * checkout/landing-page orders are created inside a DB::transaction that also writes order
 * items and decrements stock, so alerting on the raw "created" event would fire for an order
 * that then rolls back.
 *
 * Every message shows the products as photos with their full titles, which copy with one tap.
 */
class TelegramOrderObserver
{
    public bool $afterCommit = true;

    private const STATUSES = [
        'pending' => ['🕒', 'Pending'],
        'processing' => ['⚙️', 'Processing'],
        'shipped' => ['🚚', 'Shipped'],
        'delivered' => ['✅', 'Delivered'],
        'completed' => ['✅', 'Completed'],
        'cancelled' => ['❌', 'Cancelled'],
        'refunded' => ['↩️', 'Refunded'],
    ];

    public function created(Order $order): void
    {
        // An admin typing a phone order in by hand already knows about it.
        if ($order->created_by || $order->source === 'phone') {
            return;
        }

        $this->sendNewOrder($order);
    }

    /** $sample: Settings → Notifications "send a sample" button: the same message, marked as a test. */
    public function sendNewOrder(Order $order, bool $now = false, bool $sample = false): void
    {
        $order->loadMissing('items.product', 'items.combination.color', 'landingPage');
        $message = ($sample ? "🧪 <i>Sample of an earlier order</i>\n" : '') . $this->newOrderMessage($order);

        TelegramNotifier::sendWithPhotos($message, $this->photos($order), $sample ? 'test' : 'order_placed', $this->copyButtons($order), $now);
    }

    /**
     * Status changes (Processing, Shipped, Delivered, Cancelled…) and payment changes; one message
     * when both change in the same save. Switched off with "Order status updates" in Settings.
     */
    public function updated(Order $order): void
    {
        $status = $order->wasChanged('status');
        $payment = $order->wasChanged('payment_status');
        if ((!$status && !$payment) || setting('telegram_notify_status', '1') !== '1') {
            return;
        }
        $order->loadMissing('items.product', 'items.combination.color');

        $lines = [];
        if ($status) {
            [$icon, $label] = self::STATUSES[$order->status] ?? ['🔄', Str::headline((string) $order->status)];
            $old = (string) $order->getOriginal('status');
            $lines[] = "{$icon} <b>Order {$label}</b> — <code>{$order->order_number}</code>";
            $lines[] = '🔁 ' . e(self::STATUSES[$old][1] ?? Str::headline($old)) . ' → <b>' . e($label) . '</b>';
            if ($journey = $this->journey($order->status)) {
                $lines[] = $journey;
            }
        } else {
            $lines[] = "💳 <b>Payment Updated</b> — <code>{$order->order_number}</code>";
        }
        $lines[] = '🕒 ' . $this->localTime(now()) . ' · by ' . e(auth()->user()?->name ?: 'system');
        $lines[] = '';
        $lines[] = '👤 <b><code>' . e($order->shipping_name ?: 'Guest') . '</code></b>'
            . ($order->shipping_phone ? ' · 📞 <code>' . e($order->shipping_phone) . '</code>' : '');
        $lines[] = '';
        array_push($lines, ...$this->itemLines($order, 8));
        $lines[] = '';
        $lines[] = '💰 <b>' . $this->money($order->total) . '</b> · ' . $this->paymentStatusLabel($order->payment_status)
            . ($payment && $status ? ' <i>(payment updated too)</i>' : '');
        if (!$this->adminUrlButton($order)) {
            $lines[] = '🔗 <a href="' . e(route('admin.orders.show', $order)) . '">Open order</a>';
        }

        $buttons = array_values(array_filter([
            array_values(array_filter([$this->copyButton('📋 Order No', $order->order_number), $this->copyButton('📋 Phone', $order->shipping_phone)])),
            ...$this->productButtons($order),
            array_values(array_filter([$this->adminUrlButton($order)])),
        ]));

        // one photo is enough to recognise the order at a glance
        TelegramNotifier::sendWithPhotos(implode("\n", $lines), array_slice($this->photos($order), 0, 1),
            $status ? 'order_status_changed' : 'payment_status_changed', $buttons);
    }

    /**
     * Everything needed to act on an order straight from the phone — call the customer
     * (Telegram makes the number tappable), see where it ships, what to pack and what to
     * collect on delivery — without opening the admin panel. Every customer-typed value is
     * HTML-escaped (the message is sent with parse_mode=HTML).
     */
    private function newOrderMessage(Order $order): string
    {
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
        array_push($lines, ...$this->itemLines($order));
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
     * Each item: its number, its full title as tap-to-copy <code>, the variant, quantity and
     * price. The title in full: it is what gets pasted into a courier booking or a supplier chat.
     */
    private function itemLines(Order $order, int $max = 15): array
    {
        $lines = ['📦 <b>Items (' . $order->items->sum('quantity') . ')</b>'];
        foreach ($order->items->take($max)->values() as $i => $item) {
            $lines[] = $this->number($i + 1) . ' <code>' . e($item->product_name) . '</code>';
            $lines[] = '      ' . $this->itemDetail($item);
        }
        if ($order->items->count() > $max) {
            $lines[] = '… +' . ($order->items->count() - $max) . ' more';
        }

        return $lines;
    }

    private function itemDetail(OrderItem $item): string
    {
        return ($item->variant_label ? '<i>' . e($item->variant_label) . '</i> · ' : '')
            . '× ' . $item->quantity . ' — ' . $this->money($item->subtotal);
    }

    private function number(int $n): string
    {
        return $n <= 10 ? ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'][$n - 1] : "{$n}.";
    }

    /** Where the order is on its way: 🟢 Placed › 🟢 Processing › 🔵 Shipped › ⚪ Delivered. */
    private function journey(string $status): ?string
    {
        $steps = ['pending' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
        $at = array_search($status === 'completed' ? 'delivered' : $status, array_keys($steps), true);
        if ($at === false) {
            return null;
        }

        return collect(array_values($steps))
            ->map(fn ($step, $i) => ($i < $at ? '🟢' : ($i === $at ? '🔵' : '⚪')) . ' ' . ($i === $at ? "<b>{$step}</b>" : $step))
            ->implode(' › ');
    }

    /**
     * A photo per item (the chosen colour's own photo when it has one), captioned like the item
     * line. Watermark::publicUrl is the JPEG copy at a plain address Telegram can download.
     */
    private function photos(Order $order): array
    {
        if (setting('telegram_product_photos', '1') !== '1') {
            return [];
        }

        return $order->items->values()->map(function (OrderItem $item, int $i) {
            $path = $item->combination?->color?->image ?: $item->product?->image;
            $url = $path ? Watermark::publicUrl($path) : null;

            return $url ? [
                'url' => $url,
                'caption' => $this->number($i + 1) . ' <code>' . e($item->product_name) . "</code>\n" . $this->itemDetail($item),
            ] : null;
        })->filter()->values()->all();
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
            ...$this->productButtons($order),
            array_values(array_filter([
                $this->copyButton('📋 Order No', $order->order_number),
                $this->copyButton('📋 Price ' . $this->money($order->total), $amount),
            ])),
            [$this->copyButton('📋 Copy all (for courier)', $all)],
            array_values(array_filter([$this->adminUrlButton($order)])),
        ]));
    }

    /** 📋 one button per product (the first five) copying its full title, and one copying them all. */
    private function productButtons(Order $order): array
    {
        $many = $order->items->count() > 1;
        $rows = $order->items->take(5)->values()->map(fn (OrderItem $item, int $i) => [
            $this->copyButton('📋 ' . ($many ? ($i + 1) . '. ' : 'Product: ') . Str::limit($item->product_name, 28), $item->product_name),
        ])->all();
        if ($many) {
            $rows[] = [$this->copyButton('📋 All products', $order->items
                ->map(fn ($item) => $item->product_name . ($item->variant_label ? ' (' . $item->variant_label . ')' : '') . ' × ' . $item->quantity)
                ->implode("\n"))];
        }

        return array_values(array_filter($rows, fn ($row) => array_filter($row)));
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
