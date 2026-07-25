<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesReport extends Model
{
    protected $fillable = [
        'report_date', 'total_orders', 'total_revenue', 'total_discounts',
        'total_shipping', 'total_tax', 'avg_order_value',
        'cancelled_orders', 'cancelled_revenue',
        'payment_breakdown', 'status_breakdown',
        'top_category', 'top_category_revenue',
    ];

    protected $casts = [
        'report_date'          => 'date',
        'total_revenue'        => 'decimal:2',
        'total_discounts'      => 'decimal:2',
        'total_shipping'       => 'decimal:2',
        'total_tax'            => 'decimal:2',
        'avg_order_value'      => 'decimal:2',
        'cancelled_revenue'    => 'decimal:2',
        'top_category_revenue' => 'decimal:2',
        'payment_breakdown'    => 'array',
        'status_breakdown'     => 'array',
    ];

    /**
     * Recompute the snapshot row for a single calendar day from the live
     * orders/order_items tables and upsert it. Called by OrderObserver on
     * every order create/update/delete, and by the sales-report:rebuild
     * command for backfills — the row is always derived data, never
     * hand-entered, so it can't drift out of sync with real orders.
     */
    public static function rebuildForDate(CarbonInterface $date): self
    {
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();

        $activeOrders = fn () => Order::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('payment_status', '!=', 'refunded');

        $summary = $activeOrders()->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(SUM(discount), 0) as total_discounts,
                COALESCE(SUM(shipping), 0) as total_shipping,
                COALESCE(SUM(tax), 0) as total_tax,
                COALESCE(AVG(total), 0) as avg_order_value
            ')->first();

        $cancelled = Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['cancelled', 'refunded'])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as rev')
            ->first();

        $paymentBreakdown = $activeOrders()
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')->get()->toArray();

        $statusBreakdown = Order::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')->get()->toArray();

        $topCategory = OrderItem::select('categories.name as category', DB::raw('SUM(order_items.subtotal) as revenue'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$start, $end])
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->where('payment_status', '!=', 'refunded'))
            ->groupBy('categories.name')->orderByDesc('revenue')->first();

        return self::updateOrCreate(
            ['report_date' => $date->toDateString()],
            [
                'total_orders'          => $summary->total_orders ?? 0,
                'total_revenue'         => $summary->total_revenue ?? 0,
                'total_discounts'       => $summary->total_discounts ?? 0,
                'total_shipping'        => $summary->total_shipping ?? 0,
                'total_tax'             => $summary->total_tax ?? 0,
                'avg_order_value'       => $summary->avg_order_value ?? 0,
                'cancelled_orders'      => $cancelled->cnt ?? 0,
                'cancelled_revenue'     => $cancelled->rev ?? 0,
                'payment_breakdown'     => $paymentBreakdown,
                'status_breakdown'      => $statusBreakdown,
                'top_category'          => $topCategory->category ?? null,
                'top_category_revenue'  => $topCategory->revenue ?? null,
            ]
        );
    }
}
