<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderRemark;
use Illuminate\Support\Collection;

/**
 * Everything staff did on one order, in one list: remarks (who wrote what, when) interleaved
 * with the recorded changes — status, payment, fraud re-checks — so "who changed it and why"
 * reads top to bottom. Pinned remarks are returned separately to sit above it.
 */
class OrderTimeline
{
    public static function for(Order $order): array
    {
        $remarks = OrderRemark::with('user:id,name')->where('order_id', $order->id)->latest()->latest('id')->get();

        $events = AuditLog::with('user:id,name')
            ->where('model_type', Order::class)->where('model_id', $order->id)
            ->whereIn('action', ['order.status_updated', 'order.payment_updated', 'order.fraud_rechecked'])
            ->latest()->limit(40)->get()
            ->map(fn ($e) => (object) [
                'kind' => 'event',
                'at' => $e->created_at,
                'who' => $e->user?->name ?? 'System',
                'text' => self::eventText($e, $order),
                'icon' => match ($e->action) { 'order.payment_updated' => '💳', 'order.fraud_rechecked' => '🛡️', default => '🔄' },
            ]);

        $feed = $remarks->where('is_pinned', false)->map(fn ($r) => (object) ['kind' => 'remark', 'at' => $r->created_at, 'remark' => $r])
            ->concat($events)
            ->push((object) ['kind' => 'event', 'at' => $order->created_at, 'who' => $order->source === 'phone' ? ($order->createdBy?->name ?? 'Staff') : 'Customer', 'text' => 'Order placed', 'icon' => '🛍️'])
            ->sortByDesc(fn ($i) => $i->at?->timestamp ?? 0)->values();

        return [
            'pinned' => $remarks->where('is_pinned', true)->values(),
            'feed' => $feed,
            'count' => $remarks->count(),
        ];
    }

    private static function eventText(AuditLog $e, Order $order): string
    {
        $new = $e->new_values ?? [];
        $old = $e->old_values ?? [];
        $label = fn ($v) => ['processing' => 'Confirmed'][$v] ?? ucfirst((string) $v);

        return match ($e->action) {
            'order.status_updated' => 'Status: ' . (isset($old['status']) ? $label($old['status']) . ' → ' : '') . $label($new['status'] ?? ''),
            'order.payment_updated' => 'Payment: ' . (isset($old['payment_status']) ? ucfirst($old['payment_status']) . ' → ' : '') . ucfirst((string) ($new['payment_status'] ?? '')),
            'order.fraud_rechecked' => 'Fraud check re-run' . (isset($new['score']) ? ' — score ' . $new['score'] : ''),
            default => str_replace('Order ' . $order->order_number . ' ', '', $e->description),
        };
    }
}
