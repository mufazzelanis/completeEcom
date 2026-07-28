<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VendorTransaction;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Read-only by design — order fulfillment (status changes) stays
     * admin-only; sellers only see their own line items within each order.
     */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        $orders = Order::whereHas('items.product', fn ($q) => $q->where('seller_id', $vendor->id))
            ->with(['items' => fn ($q) => $q->whereHas('product', fn ($q2) => $q2->where('seller_id', $vendor->id))->with('product')])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $ledgerByItemId = VendorTransaction::where('vendor_id', $vendor->id)
            ->pluck('status', 'order_item_id');

        return view('seller.orders.index', compact('orders', 'ledgerByItemId'));
    }
}
