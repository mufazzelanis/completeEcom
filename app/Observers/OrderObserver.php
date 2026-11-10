<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\SalesReport;
use App\Services\VendorEarningsService;

class OrderObserver
{
    /**
     * After the checkout transaction commits: on the raw "created" event the order's items aren't
     * saved yet, so vendor earnings saw an empty order, and the empty items list it loaded was
     * then reused by the Telegram alert ("Items (0)", no photos).
     */
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
        VendorEarningsService::syncForOrder($order);
    }

    public function updated(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
        VendorEarningsService::syncForOrder($order);
    }

    public function deleted(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
    }
}
