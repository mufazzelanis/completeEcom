<?php

namespace App\Services;

use App\Models\Order;
use App\Models\VendorTransaction;
use Illuminate\Support\Facades\Log;

class VendorEarningsService
{
    /**
     * Keeps the vendor_transactions ledger in sync with an order's current
     * status/payment_status — called from OrderObserver so every path that
     * touches an order (admin panel, checkout, API) stays consistent without
     * duplicating calls across controllers.
     */
    public static function syncForOrder(Order $order): void
    {
        try {
            $order->loadMissing('items.product');

            foreach ($order->items as $item) {
                $product = $item->product;
                if (!$product || !$product->seller_id) {
                    continue;
                }

                $existing = VendorTransaction::where('order_item_id', $item->id)->first();

                // Once a payout has been recorded, later edits to the order must
                // never silently un-pay a seller.
                if ($existing && $existing->status === 'paid') {
                    continue;
                }

                $status = self::resolveStatus($order);
                $commissionRate = (float) ($product->seller->commission_rate ?? 0);
                $saleAmount = (float) $item->subtotal;
                $commissionAmount = round($saleAmount * ($commissionRate / 100), 2);
                $netAmount = $saleAmount - $commissionAmount;

                VendorTransaction::updateOrCreate(
                    ['order_item_id' => $item->id],
                    [
                        'vendor_id' => $product->seller_id,
                        'order_id' => $order->id,
                        'sale_amount' => $saleAmount,
                        'commission_rate' => $commissionRate,
                        'commission_amount' => $commissionAmount,
                        'net_amount' => $netAmount,
                        'status' => $status,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error('VendorEarningsService sync failed: ' . $e->getMessage());
        }
    }

    private static function resolveStatus(Order $order): string
    {
        if (in_array($order->status, ['cancelled', 'refunded'], true) || in_array($order->payment_status, ['failed', 'refunded'], true)) {
            return 'cancelled';
        }

        if ($order->payment_status === 'paid' && $order->status === 'delivered') {
            return 'available';
        }

        return 'hold';
    }
}
