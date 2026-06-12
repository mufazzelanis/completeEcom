<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
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

        $pendingRewards = ReferralReward::where('status', 'pending')->with('referrer', 'referee', 'order')->latest()->get();

        $stats = [
            'total_codes'   => ReferralCode::count(),
            'active_codes'  => ReferralCode::where('total_uses', '>', 0)->count(),
            'pending_count' => $pendingRewards->count(),
            'total_paid'    => ReferralReward::where('status', 'paid')->sum('reward_amount'),
            'total_earned'  => ReferralReward::whereIn('status', ['approved', 'paid'])->sum('reward_amount'),
        ];

        return view('admin.referrals.index', compact('codes', 'pendingRewards', 'stats'));
    }

    public function updateReward(Request $request, ReferralReward $reward)
    {
        $request->validate(['status' => 'required|in:approved,paid,rejected']);

        $old = $reward->status;
        $reward->update(['status' => $request->status]);

        if ($request->status === 'approved' || $request->status === 'paid') {
            // Update total_earned on the referral code
            $reward->referralCode->increment('total_earned', $reward->reward_amount);
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
        return view('admin.referrals.settings');
    }
}
