<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $vendor = $request->user()->vendor;

        return view('seller.profile.edit', compact('vendor'));
    }

    /**
     * Never writes directly to the vendor's live columns — every submitted
     * change is staged in `pending_changes` and only takes effect once an
     * admin approves it (Admin\VendorController::approveProfile()).
     */
    public function update(Request $request)
    {
        $vendor = $request->user()->vendor;

        $request->validate([
            'business_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'payout_method' => 'nullable|string|max:100',
            'payout_account_name' => 'nullable|string|max:255',
            'payout_account_number' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
        ]);

        // A logo field can be present in the request but fail to arrive as a
        // real file (server temp-dir misconfiguration, a dropped connection,
        // an oversized upload past PHP's own limit before Laravel ever sees
        // it) — silently ignoring that would submit a proposal missing the
        // one change the seller actually came here to make, with no clue why.
        if ($request->hasFile('logo') && !$request->file('logo')->isValid()) {
            return back()->withInput()->with('error', 'Logo upload failed — the file may be too large or the upload was interrupted. Please try again with a smaller image.');
        }

        $changes = $request->only(['business_name', 'phone', 'email', 'website', 'description', 'payout_method']);
        $changes['payout_details'] = [
            'account_name' => $request->payout_account_name,
            'account_number' => $request->payout_account_number,
        ];

        if ($request->hasFile('logo')) {
            $changes['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update([
            'pending_changes' => $changes,
            'profile_status' => 'pending',
            'profile_rejection_reason' => null,
        ]);

        return redirect()->route('seller.profile.edit')
            ->with('success', 'Your profile changes have been submitted and are awaiting admin approval.');
    }
}
