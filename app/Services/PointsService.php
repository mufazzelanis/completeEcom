<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Setting;

class PointsService
{
    public static function awardPurchasePoints(Order $order): void
    {
        if ($order->points_awarded_at || ! $order->user_id) {
            return;
        }

        $points = (int) Setting::get('purchase.reward_points', 5);

        if ($points <= 0) {
            return;
        }

        $order->user->increment('points_balance', $points);
        $order->update(['points_awarded_at' => now()]);

        PointTransaction::create([
            'user_id' => $order->user_id,
            'type' => 'purchase_earned',
            'points' => $points,
            'order_id' => $order->id,
            'description' => "Earned {$points} pts for order {$order->order_number}",
        ]);
    }
}
