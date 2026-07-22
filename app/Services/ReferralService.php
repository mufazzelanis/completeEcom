<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\Setting;

class ReferralService
{
    public static function maybeReward(Order $order): void
    {
        $referee = $order->user;

        if (! $referee || ! $referee->referred_by) {
            return;
        }

        $orderCount = Order::where('user_id', $referee->id)->count();

        if ($orderCount !== 1) {
            return;
        }

        $referralCode = ReferralCode::where('user_id', $referee->referred_by)->first();

        if (! $referralCode) {
            return;
        }

        $reward = ReferralReward::firstOrCreate(
            ['referral_code_id' => $referralCode->id, 'referee_id' => $referee->id],
            [
                'referrer_id' => $referralCode->user_id,
                'order_id' => $order->id,
                'reward_amount' => (int) Setting::get('referral.reward_amount', 10),
                'status' => 'paid',
            ]
        );

        if ($reward->wasRecentlyCreated) {
            $referralCode->increment('total_earned', $reward->reward_amount);
            $referralCode->user->increment('points_balance', $reward->reward_amount);

            PointTransaction::create([
                'user_id' => $referralCode->user_id,
                'type' => 'referral_earned',
                'points' => $reward->reward_amount,
                'order_id' => $order->id,
                'description' => "Earned {$reward->reward_amount} pts for referring {$referee->name}",
            ]);
        }
    }
}
