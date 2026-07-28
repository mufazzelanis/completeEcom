<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\SalesReport;
use App\Services\VendorEarningsService;

class OrderObserver
{
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
