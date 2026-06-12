<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use App\Models\ReferralReward;

class CustomerReferralController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $referral = ReferralCode::where('user_id', $user->id)->first();
        if (!$referral) {
            $referral = ReferralCode::generateFor($user);
        }

        $rewards = ReferralReward::where('referrer_id', $user->id)
            ->with('referee')
            ->latest()
            ->paginate(10);

        $stats = [
            'total_uses'   => $referral->total_uses,
            'total_earned' => $referral->total_earned,
            'pending'      => ReferralReward::where('referrer_id', $user->id)->where('status', 'pending')->count(),
            'paid'         => ReferralReward::where('referrer_id', $user->id)->where('status', 'paid')->sum('reward_amount'),
        ];

        return view('account.referral.index', compact('referral', 'rewards', 'stats'));
    }
}
