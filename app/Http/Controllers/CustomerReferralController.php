<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\ReferralCode;

class CustomerReferralController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $referral = ReferralCode::where('user_id', $user->id)->first();
        if (!$referral) {
            $referral = ReferralCode::generateFor($user);
        }

        $pointTransactions = $user->pointTransactions()->with('order')->latest()->paginate(10);

        $stats = [
            'total_uses'      => $referral->total_uses,
            'total_earned'    => $referral->total_earned,
            'points_redeemed' => abs(PointTransaction::where('user_id', $user->id)->where('type', 'redeemed')->sum('points')),
            'points_balance'  => $user->points_balance,
        ];

        return view('account.referral.index', compact('referral', 'pointTransactions', 'stats'));
    }
}
