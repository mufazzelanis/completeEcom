<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\SalesReport;

class OrderObserver
{
    public function created(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
    }

    public function updated(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
    }

    public function deleted(Order $order): void
    {
        SalesReport::rebuildForDate($order->created_at->copy());
    }
}
