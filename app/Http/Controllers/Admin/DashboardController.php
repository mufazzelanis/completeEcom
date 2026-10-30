<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $paidOrders       = Order::where('payment_status', 'paid')->count();
        $totalRevenue     = Order::where('payment_status', 'paid')->sum('total');
        $cancelledOrders  = Order::whereIn('status', ['cancelled', 'refunded'])->count();
        $cancelledRevenue = Order::whereIn('status', ['cancelled', 'refunded'])->sum('total');

        $stats = [
            'total_orders'      => Order::count(),
            'pending_orders'    => Order::where('status', 'pending')->count(),
            'paid_orders'       => $paidOrders,
            'cancelled_orders'  => $cancelledOrders,
            'cancelled_revenue' => $cancelledRevenue,
            'total_products'    => Product::count(),
            'total_users'       => User::where('role', 'customer')->count(),
            'new_customers'     => User::where('role', 'customer')->where('created_at', '>=', now()->startOfMonth())->count(),
            'total_revenue'     => $totalRevenue,
            'avg_order_value'   => $paidOrders > 0 ? $totalRevenue / $paidOrders : 0,
            'today_orders'      => Order::whereDate('created_at', today())->count(),
            'total_categories'  => Category::count(),
            'out_of_stock'      => Product::where('is_active', true)->where('stock', 0)->count(),
        ];

        $recentOrders = $this->recentOrders('all');
        $orderTabCounts = $this->orderTabCounts();

        // Top sellers by actual paid revenue — unit price comes from the live
        // product record, total comes from summed order_items.subtotal, so both
        // "product price" and "total sold" are real numbers, not placeholders.
        $topProducts = OrderItem::select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as qty_sold'),
                DB::raw('SUM(subtotal) as revenue')
            )
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->take(5)
            ->with('product:id,price,sale_price,image')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'orderTabCounts', 'topProducts'));
    }

    /** Dashboard "Recent orders" tabs. */
    public const ORDER_TABS = ['all' => 'All', 'pending' => 'To confirm', 'processing' => 'To ship', 'shipped' => 'On the way', 'today' => 'Today'];

    private function recentOrders(string $tab)
    {
        $q = Order::with(['user:id,name', 'items:id,order_id,product_id,product_name,quantity', 'items.product:id,image'])
            ->withCount('items');
        match ($tab) {
            'pending', 'processing', 'shipped' => $q->where('status', $tab),
            'today' => $q->where('created_at', '>=', now(setting('timezone', 'Asia/Dhaka'))->startOfDay()->utc()),
            default => null,
        };

        return $q->latest()->take(8)->get();
    }

    private function orderTabCounts(): array
    {
        $by = Order::whereIn('status', ['pending', 'processing', 'shipped'])->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return [
            'all' => null,
            'pending' => (int) ($by['pending'] ?? 0),
            'processing' => (int) ($by['processing'] ?? 0),
            'shipped' => (int) ($by['shipped'] ?? 0),
            'today' => Order::where('created_at', '>=', now(setting('timezone', 'Asia/Dhaka'))->startOfDay()->utc())->count(),
        ];
    }

    /** Tab switch + the 45-second live refresh of the dashboard's recent orders. */
    public function orders(Request $request)
    {
        $tab = array_key_exists($request->tab, self::ORDER_TABS) ? $request->tab : 'all';
        $orders = $this->recentOrders($tab);

        return response()->json([
            'html' => view('admin.dashboard._orders', ['orders' => $orders, 'newSince' => (int) $request->since])->render(),
            'ids' => $orders->pluck('id'),
            'counts' => $this->orderTabCounts(),
            'latest' => (int) Order::max('id'),
        ]);
    }
}
