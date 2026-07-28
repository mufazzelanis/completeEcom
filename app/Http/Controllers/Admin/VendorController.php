<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\VendorTransaction;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    private const DOCUMENT_FIELDS = ['nid_front_image', 'nid_back_image', 'birth_certificate_image'];
    public function index(Request $request)
    {
        $query = Vendor::with('user')->withCount('products');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $vendors = $query->latest()->paginate(20)->withQueryString();
        $pendingCount = Vendor::where('status', 'pending')->count();

        return view('admin.vendors.index', compact('vendors', 'pendingCount'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('user', 'approver');
        $vendor->loadCount('products');

        $products = $vendor->products()->with('category')->latest()->paginate(10, ['*'], 'products');
        $transactions = VendorTransaction::where('vendor_id', $vendor->id)->latest()->paginate(20, ['*'], 'transactions');
        $earnings = [
            'hold'      => VendorTransaction::where('vendor_id', $vendor->id)->where('status', 'hold')->sum('net_amount'),
            'available' => VendorTransaction::where('vendor_id', $vendor->id)->where('status', 'available')->sum('net_amount'),
            'paid'      => VendorTransaction::where('vendor_id', $vendor->id)->where('status', 'paid')->sum('net_amount'),
            'lifetime'  => VendorTransaction::where('vendor_id', $vendor->id)->whereIn('status', ['available', 'paid'])->sum('net_amount'),
        ];
        $payouts = VendorPayout::where('vendor_id', $vendor->id)->with('processedBy')->latest()->paginate(10, ['*'], 'payouts');

        return view('admin.vendors.show', compact('vendor', 'products', 'transactions', 'earnings', 'payouts'));
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'business_name'   => 'required|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'website'         => 'nullable|url|max:255',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'payout_method'   => 'nullable|string|max:100',
            'description'     => 'nullable|string|max:2000',
        ]);

        $vendor->update($request->only([
            'business_name', 'phone', 'email', 'website', 'commission_rate', 'payout_method', 'description',
        ]));

        return redirect()->route('admin.vendors.show', $vendor)->with('success', 'Vendor updated.');
    }

    /**
     * Manual payout ledger — bundles every currently-`available` transaction
     * into one `VendorPayout` batch (amount, method, reference) and marks
     * those transactions `paid`, linked back to the batch. No payment-gateway
     * call; admin has already sent the money by hand (bank transfer, bKash,
     * etc.) and this just records it for both the admin and seller to see.
     */
    public function payout(Request $request, Vendor $vendor)
    {
        $request->validate([
            'method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $available = VendorTransaction::where('vendor_id', $vendor->id)->where('status', 'available')->get();

        if ($available->isEmpty()) {
            return back()->with('error', 'No available balance to pay out.');
        }

        $payout = DB::transaction(function () use ($vendor, $available, $request) {
            $payout = VendorPayout::create([
                'vendor_id' => $vendor->id,
                'amount' => $available->sum('net_amount'),
                'method' => $request->method ?: $vendor->payout_method,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'paid_by' => auth()->id(),
            ]);

            VendorTransaction::whereIn('id', $available->pluck('id'))
                ->update(['status' => 'paid', 'paid_at' => now(), 'payout_id' => $payout->id]);

            return $payout;
        });

        NotificationDispatcher::customer('vendor_payout_sent', $vendor->user, [
            'business_name' => $vendor->business_name,
            'amount' => number_format($payout->amount, 2),
            'url' => route('seller.reports.index'),
        ]);

        return back()->with('success', "Payout of ৳" . number_format($payout->amount, 2) . " recorded for {$available->count()} transaction(s).");
    }

    public function approve(Vendor $vendor)
    {
        $vendor->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $vendor->user->update(['role' => 'vendor']);

        NotificationDispatcher::customer('vendor_approved', $vendor->user, [
            'business_name' => $vendor->business_name,
            'url' => route('seller.dashboard'),
        ]);

        return back()->with('success', 'Vendor approved.');
    }

    public function reject(Request $request, Vendor $vendor)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:255']);

        $vendor->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        NotificationDispatcher::customer('vendor_rejected', $vendor->user, [
            'business_name' => $vendor->business_name,
            'reason' => $vendor->rejection_reason,
            'url' => route('vendor.apply'),
        ]);

        return back()->with('success', 'Vendor application rejected.');
    }

    public function suspend(Vendor $vendor)
    {
        $vendor->update(['status' => 'suspended']);

        NotificationDispatcher::customer('vendor_suspended', $vendor->user, [
            'business_name' => $vendor->business_name,
            'url' => route('vendor.apply'),
        ]);

        return back()->with('success', 'Vendor suspended.');
    }

    public function approveProfile(Vendor $vendor)
    {
        if ($vendor->profile_status !== 'pending') {
            return back()->with('error', 'No pending profile change to approve.');
        }

        $vendor->applyPendingChanges();

        NotificationDispatcher::customer('vendor_profile_approved', $vendor->user, [
            'business_name' => $vendor->business_name,
            'url' => route('seller.profile.edit'),
        ]);

        return back()->with('success', 'Profile changes approved and are now live.');
    }

    public function rejectProfile(Request $request, Vendor $vendor)
    {
        $request->validate(['profile_rejection_reason' => 'required|string|max:255']);

        // pending_changes is kept (not cleared) so the seller can still see exactly
        // what they submitted while revising it on their profile page.
        $vendor->update([
            'profile_status' => 'rejected',
            'profile_rejection_reason' => $request->profile_rejection_reason,
        ]);

        NotificationDispatcher::customer('vendor_profile_rejected', $vendor->user, [
            'business_name' => $vendor->business_name,
            'reason' => $request->profile_rejection_reason,
            'url' => route('seller.profile.edit'),
        ]);

        return back()->with('success', 'Profile changes rejected.');
    }

    public function requestCorrection(Request $request, Vendor $vendor)
    {
        $request->validate(['correction_notes' => 'required|string|max:1000']);

        $vendor->update([
            'status' => 'needs_correction',
            'correction_notes' => $request->correction_notes,
        ]);

        NotificationDispatcher::customer('vendor_correction_requested', $vendor->user, [
            'business_name' => $vendor->business_name,
            'notes' => $request->correction_notes,
            'url' => route('vendor.apply'),
        ]);

        return back()->with('success', 'Correction requested from vendor.');
    }

    /**
     * Streams an identity document from the `private` disk (serve: false, no
     * public URL). Field name is whitelisted — never accept a raw path from
     * the request — to prevent path traversal into arbitrary storage files.
     */
    public function document(Vendor $vendor, string $field)
    {
        abort_unless(in_array($field, self::DOCUMENT_FIELDS, true), 404);

        $path = $vendor->$field;
        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response($path);
    }
}
