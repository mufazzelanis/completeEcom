<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public static function awardPurchasePoints(Order $order): void
    {
        // DB::transaction() here (rather than requiring the caller to already be in one)
        // makes the lock below effective either way: a savepoint inside the caller's own
        // transaction (Admin\PaymentController::verify()), or its own transaction when the
        // caller isn't wrapped in one (Admin\OrderController::update()). Without this, two
        // concurrent payment-status-to-paid updates for the same order could both read
        // points_awarded_at as still null and double-credit the customer's points balance.
        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || $locked->points_awarded_at || ! $locked->user_id) {
                return;
            }

            $points = (int) Setting::get('purchase.reward_points', 5);

            if ($points <= 0) {
                return;
            }

            $locked->user->increment('points_balance', $points);
            $locked->update(['points_awarded_at' => now()]);

            PointTransaction::create([
                'user_id' => $locked->user_id,
                'type' => 'purchase_earned',
                'points' => $points,
                'order_id' => $locked->id,
                'description' => "Earned {$points} pts for order {$locked->order_number}",
            ]);
        });
    }
}
