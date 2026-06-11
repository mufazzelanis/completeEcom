<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders'    => Order::count(),
            'pending_orders'  => Order::where('status', 'pending')->count(),
            'total_products'  => Product::count(),
            'total_users'     => User::where('role', 'customer')->count(),
            'total_revenue'   => Order::where('payment_status', 'paid')->sum('total'),
            'today_orders'    => Order::whereDate('created_at', today())->count(),
            'total_categories'=> Category::count(),
        ];

        $recentOrders = Order::with('user')->latest()->take(10)->get();
        $topProducts = Product::withCount('reviews')->orderByDesc('views')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'topProducts'));
    }
}
