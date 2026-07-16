<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
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

        return view('admin.vendors.show', compact('vendor'));
    }

    public function approve(Vendor $vendor)
    {
        $vendor->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $vendor->user->update(['role' => 'vendor']);

        return back()->with('success', 'Vendor approved.');
    }

    public function reject(Request $request, Vendor $vendor)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:255']);

        $vendor->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Vendor application rejected.');
    }

    public function suspend(Vendor $vendor)
    {
        $vendor->update(['status' => 'suspended']);

        return back()->with('success', 'Vendor suspended.');
    }
}
