<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ReferralProgramController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferralCode::with('user')->withCount('rewards');

        if ($request->filled('search')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'))
                ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        $codes = $query->orderByDesc('total_uses')->paginate(20);

        $pointActivity = PointTransaction::with('user', 'order')
            ->when($request->filled('activity_search'), fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', '%' . $request->activity_search . '%')
                ->orWhere('email', 'like', '%' . $request->activity_search . '%')))
            ->latest()
            ->paginate(20, ['*'], 'activity_page');

        $stats = [
            'total_codes'   => ReferralCode::count(),
            'active_codes'  => ReferralCode::where('total_uses', '>', 0)->count(),
            'total_referral_points' => ReferralReward::where('status', 'paid')->sum('reward_amount'),
            'total_points_awarded'  => PointTransaction::where('points', '>', 0)->sum('points'),
            'total_points_redeemed' => abs(PointTransaction::where('type', 'redeemed')->sum('points')),
        ];

        return view('admin.referrals.index', compact('codes', 'pointActivity', 'stats'));
    }

    public function updateReward(Request $request, ReferralReward $reward)
    {
        $request->validate(['status' => 'required|in:approved,paid,rejected']);

        $old = $reward->status;
        $reward->update(['status' => $request->status]);

        $shouldCredit = in_array($old, ['pending', 'rejected'], true)
            && in_array($request->status, ['approved', 'paid'], true);

        if ($shouldCredit) {
            $reward->referralCode->increment('total_earned', $reward->reward_amount);
            $reward->referrer->increment('points_balance', $reward->reward_amount);
        }

        AuditLogger::log(
            'referral.reward_updated',
            "Referral reward for {$reward->referrer->name} changed from {$old} to {$request->status}",
            $reward,
            ['status' => $old],
            ['status' => $request->status]
        );

        return back()->with('success', 'Reward status updated.');
    }

    public function settings()
    {
        $rewardAmount = Setting::get('referral.reward_amount', 10);
        $purchasePoints = Setting::get('purchase.reward_points', 5);
        $redeemRate = Setting::get('points.redeem_rate', 1);

        return view('admin.referrals.settings', compact('rewardAmount', 'purchasePoints', 'redeemRate'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'reward_amount'   => 'required|integer|min:0',
            'purchase_points' => 'required|integer|min:0',
            'redeem_rate'     => 'required|numeric|min:0.01',
        ]);

        Setting::set('referral.reward_amount', $request->reward_amount, 'referral');
        Setting::set('purchase.reward_points', $request->purchase_points, 'referral');
        Setting::set('points.redeem_rate', $request->redeem_rate, 'referral');

        return back()->with('success', 'Rewards & points settings updated.');
    }
}
