<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
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

        $recentOrders = Order::with('user')->latest()->take(10)->get();

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

        return view('admin.dashboard', compact('stats', 'recentOrders', 'topProducts'));
    }
}
