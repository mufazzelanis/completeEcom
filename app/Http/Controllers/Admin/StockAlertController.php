<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAlert;
use Illuminate\Http\Request;

/**
 * Admin → Stock Alerts: who's waiting for which out-of-stock product — demand worth
 * restocking for — and, once a product is back, everyone to tell (WhatsApp / Call, or the
 * automatic SMS when a gateway is set up).
 */
class StockAlertController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'waiting') === 'notified' ? 'notified' : 'waiting';

        $groups = StockAlert::where('status', $status)
            ->selectRaw('product_id, COUNT(*) as people, MAX(created_at) as latest')
            ->groupBy('product_id')
            ->orderByDesc('people')
            ->paginate(20)
            ->withQueryString();

        $products = Product::whereIn('id', $groups->pluck('product_id'))->get()->keyBy('id');
        $alerts = StockAlert::where('status', $status)->whereIn('product_id', $groups->pluck('product_id'))
            ->latest()->get()->groupBy('product_id');

        return view('admin.stock-alerts.index', [
            'groups' => $groups,
            'products' => $products,
            'alerts' => $alerts,
            'status' => $status,
            'counts' => [
                'waiting' => StockAlert::where('status', 'waiting')->count(),
                'products' => StockAlert::where('status', 'waiting')->distinct()->count('product_id'),
                'notified' => StockAlert::where('status', 'notified')->where('notified_at', '>=', now()->startOfMonth())->count(),
            ],
            'smsReady' => CheckoutLeadController::smsReady(),
        ]);
    }

    /** Mark everyone waiting for this product as told (after messaging them yourself). */
    public function notified(Product $product)
    {
        $n = StockAlert::where('product_id', $product->id)->where('status', 'waiting')
            ->update(['status' => 'notified', 'notified_at' => now()]);

        return back()->with('success', "{$n} marked as notified.");
    }

    public function destroy(StockAlert $alert)
    {
        $alert->delete();

        return back()->with('success', 'Removed.');
    }
}
