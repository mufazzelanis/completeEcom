<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\VendorTransaction;
use Illuminate\Http\Request;

class VendorPaymentController extends Controller
{
    /**
     * One consolidated screen for "which seller sold how much, and how much
     * has each been paid" — separate from the per-vendor show page (which
     * only covers one seller at a time) so admin can see every seller's
     * sales/payment position, and every payout ever recorded, in one place.
     */
    public function index(Request $request)
    {
        $query = Vendor::withCount('products')
            ->withSum('transactions as total_sales', 'sale_amount')
            ->withSum('transactions as total_commission', 'commission_amount')
            ->withSum(['transactions as hold_amount' => fn ($q) => $q->where('status', 'hold')], 'net_amount')
            ->withSum(['transactions as available_amount' => fn ($q) => $q->where('status', 'available')], 'net_amount')
            ->withSum(['transactions as paid_amount' => fn ($q) => $q->where('status', 'paid')], 'net_amount');

        if ($request->filled('search')) {
            $query->where('business_name', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('has_balance')) {
            $query->having('available_amount', '>', 0);
        }

        $sortBy = $request->get('sort_by', 'total_sales');
        match ($sortBy) {
            'available' => $query->orderByDesc('available_amount'),
            'paid'      => $query->orderByDesc('paid_amount'),
            'name'      => $query->orderBy('business_name'),
            default     => $query->orderByDesc('total_sales'),
        };

        $vendors = $query->paginate(20)->withQueryString();

        // Platform-wide totals, independent of the vendor filter/pagination above.
        $totals = [
            'total_sales'      => VendorTransaction::sum('sale_amount'),
            'total_commission' => VendorTransaction::sum('commission_amount'),
            'hold'             => VendorTransaction::where('status', 'hold')->sum('net_amount'),
            'available'        => VendorTransaction::where('status', 'available')->sum('net_amount'),
            'paid'             => VendorTransaction::where('status', 'paid')->sum('net_amount'),
        ];

        $recentPayouts = VendorPayout::with(['vendor', 'processedBy'])->latest()->paginate(15, ['*'], 'payouts');

        return view('admin.vendor-payments.index', compact('vendors', 'totals', 'recentPayouts'));
    }
}
