<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VendorTransaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        $stats = [
            'total'    => Product::where('seller_id', $vendor->id)->count(),
            'pending'  => Product::where('seller_id', $vendor->id)->where('approval_status', 'pending')->count(),
            'approved' => Product::where('seller_id', $vendor->id)->where('approval_status', 'approved')->count(),
            'rejected' => Product::where('seller_id', $vendor->id)->where('approval_status', 'rejected')->count(),
        ];

        $transactions = VendorTransaction::where('vendor_id', $vendor->id);
        $earnings = [
            'sold_count' => (clone $transactions)->whereIn('status', ['hold', 'available', 'paid'])->count(),
            'hold'       => (clone $transactions)->where('status', 'hold')->sum('net_amount'),
            'available'  => (clone $transactions)->where('status', 'available')->sum('net_amount'),
            'paid'       => (clone $transactions)->where('status', 'paid')->sum('net_amount'),
            'cancelled_count' => (clone $transactions)->where('status', 'cancelled')->count(),
        ];

        $recentProducts = Product::where('seller_id', $vendor->id)->latest()->take(5)->get();

        return view('seller.dashboard', compact('vendor', 'stats', 'earnings', 'recentProducts'));
    }
}
