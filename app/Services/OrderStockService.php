<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class OrderStockService
{
    private const TERMINAL = ['cancelled', 'refunded'];

    /**
     * Restore stock for an order's items once it reaches cancelled/refunded.
     * Idempotent by checking for a prior restoration, so it's safe to call from
     * every entry point that can mark an order cancelled/refunded (status editor,
     * payment refund, customer self-cancel) without double-crediting stock.
     *
     * Mirrors exactly what CheckoutController::store() decremented for each line
     * (bundle -> components, variable -> its combination) rather than blindly
     * crediting the ordered product's own stock column, which for a bundle isn't
     * what was actually decremented and for a variable combination is only half
     * of what was.
     */
    public static function restoreIfNeeded(Order $order, string $newStatus, int $adjustedBy): void
    {
        if (! in_array($newStatus, self::TERMINAL, true)) {
            return;
        }

        if (StockAdjustment::where('order_id', $order->id)->where('type', 'return_in')->exists()) {
            return;
        }

        DB::transaction(function () use ($order, $newStatus, $adjustedBy) {
            $order->load(['items.product.bundleItems.itemProduct', 'items.combination']);

            foreach ($order->items as $item) {
                if (! $item->product) {
                    continue;
                }

                if ($item->product->isBundle()) {
                    foreach ($item->product->bundleItems as $bundleItem) {
                        if (! $bundleItem->itemProduct) {
                            continue;
                        }

                        $qty = $bundleItem->quantity * $item->quantity;
                        $before = $bundleItem->itemProduct->stock;
                        $bundleItem->itemProduct->increment('stock', $qty);

                        StockAdjustment::create([
                            'product_id'   => $bundleItem->item_product_id,
                            'order_id'     => $order->id,
                            'type'         => 'return_in',
                            'quantity'     => $qty,
                            'stock_before' => $before,
                            'stock_after'  => $before + $qty,
                            'reference'    => $order->order_number,
                            'reason'       => "Order {$newStatus} (bundle: {$item->product->name})",
                            'adjusted_by'  => $adjustedBy,
                        ]);
                    }

                    $item->product->refreshBundleStock();
                    continue;
                }

                if ($item->product_variant_combination_id && $item->combination) {
                    $item->combination->increment('stock', $item->quantity);
                }

                $before = $item->product->stock;
                $item->product->increment('stock', $item->quantity);

                StockAdjustment::create([
                    'product_id'   => $item->product_id,
                    'order_id'     => $order->id,
                    'type'         => 'return_in',
                    'quantity'     => $item->quantity,
                    'stock_before' => $before,
                    'stock_after'  => $before + $item->quantity,
                    'reference'    => $order->order_number,
                    'reason'       => "Order {$newStatus}",
                    'adjusted_by'  => $adjustedBy,
                ]);
            }
        });
    }
}
