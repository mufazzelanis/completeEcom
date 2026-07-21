<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Exports\SalesReportExport;
use App\Exports\RevenueReportExport;
use App\Exports\ProductsReportExport;
use App\Exports\CustomersReportExport;
use App\Exports\InventoryReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    // ── helpers ──────────────────────────────────────────────────────────
    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::now()->subDays(29)->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::now()->endOfDay();
        return [$from, $to];
    }

    // ── overview dashboard ────────────────────────────────────────────────
    public function index()
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Key metrics
        $todayRevenue   = Order::whereDate('created_at', $today)->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->sum('total');
        $monthRevenue   = Order::where('created_at', '>=', $thisMonth)->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->sum('total');
        $lastMonthRev   = Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->sum('total');
        $revenueGrowth  = $lastMonthRev > 0 ? round((($monthRevenue - $lastMonthRev) / $lastMonthRev) * 100, 1) : 0;

        $totalOrders    = Order::whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->count();
        $monthOrders    = Order::where('created_at', '>=', $thisMonth)->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->count();
        $pendingOrders  = Order::where('status', 'pending')->count();
        $totalCustomers = User::where('role', '!=', 'admin')->count();
        $newCustomers   = User::where('role', '!=', 'admin')->where('created_at', '>=', $thisMonth)->count();
        $totalProducts  = Product::where('is_active', true)->count();
        $outOfStock     = Product::where('is_active', true)->where('stock', 0)->count();

        // Revenue last 30 days (daily)
        $last30 = Order::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->groupBy('date')->orderBy('date')->get();

        // Orders by status
        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')->orderByDesc('count')->get();

        // Top 5 products this month
        $topProducts = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as qty_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', $thisMonth)->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('product_id', 'product_name')->orderByDesc('revenue')->take(5)->get();

        // Recent orders
        $recentOrders = Order::with('user')->latest()->take(8)->get();

        return view('admin.reports.index', compact(
            'todayRevenue','monthRevenue','revenueGrowth','totalOrders','monthOrders',
            'pendingOrders','totalCustomers','newCustomers','totalProducts','outOfStock',
            'last30','ordersByStatus','topProducts','recentOrders','lastMonthRev'
        ));
    }

    // ── sales report ──────────────────────────────────────────────────────
    public function sales(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $groupBy = $request->get('group_by', 'day');

        $dateFormat = match($groupBy) {
            'month' => '%Y-%m',
            'week'  => '%x-%v',
            default => '%Y-%m-%d',
        };

        $salesTrend = Order::select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(discount) as discounts'),
                DB::raw('SUM(shipping) as shipping'),
                DB::raw('AVG(total) as avg_order')
            )
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->groupBy('period')->orderBy('period')->get();

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->selectRaw('COUNT(*) as total_orders, SUM(total) as total_revenue, SUM(discount) as total_discounts, SUM(shipping) as total_shipping, AVG(total) as avg_order_value')
            ->first();

        $cancelledRevenue = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['cancelled','refunded'])->sum('total');

        $byStatus = Order::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('status')->orderByDesc('count')->get();

        $byPayment = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')->orderByDesc('revenue')->get();

        $byCategoryQuery = OrderItem::select('categories.name as category', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('categories.name')->orderByDesc('revenue');

        $categoryRevenueTotal = (clone $byCategoryQuery)->get()->sum('revenue');
        $byCategory = (clone $byCategoryQuery)->take(10)->get();

        return view('admin.reports.sales', compact(
            'salesTrend','summary','cancelledRevenue','byStatus','byPayment','byCategory',
            'categoryRevenueTotal','from','to','groupBy'
        ));
    }

    // ── revenue report ─────────────────────────────────────────────────────
    public function revenue(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->selectRaw('SUM(total) as gross_revenue, SUM(discount) as total_discounts, SUM(shipping) as shipping_revenue, SUM(tax) as tax_collected, COUNT(*) as order_count')
            ->first();

        // Prev period for comparison
        $diff = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($diff);
        $prevTo   = $from->copy()->subDay();
        $prevSummary = Order::whereBetween('created_at', [$prevFrom, $prevTo])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->selectRaw('SUM(total) as gross_revenue, COUNT(*) as order_count')
            ->first();

        $revenueGrowth = ($prevSummary->gross_revenue ?? 0) > 0
            ? round((($summary->gross_revenue - $prevSummary->gross_revenue) / $prevSummary->gross_revenue) * 100, 1)
            : 0;

        // Revenue by category
        $byCategory = OrderItem::select('categories.name as category', DB::raw('SUM(order_items.subtotal) as revenue'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('categories.name')->orderByDesc('revenue')->get();

        // Revenue by brand
        $byBrand = OrderItem::select('brands.name as brand', DB::raw('SUM(order_items.subtotal) as revenue'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('brands.name')->orderByDesc('revenue')->take(10)->get();

        // Monthly trend (always 12 months)
        $monthlyTrend = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(discount) as discounts'),
                DB::raw('COUNT(*) as orders')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->groupBy('month')->orderBy('month')->get();

        // Coupon usage
        $couponImpact = Order::whereBetween('created_at', [$from, $to])
            ->whereNotNull('coupon_code')->where('discount', '>', 0)
            ->selectRaw('COUNT(*) as orders_with_coupon, SUM(discount) as total_discount, AVG(discount) as avg_discount')
            ->first();

        $ordersWithCoupon = Order::whereBetween('created_at', [$from, $to])
            ->whereNotNull('coupon_code')->where('discount', '>', 0)->count();
        $totalOrdersInRange = Order::whereBetween('created_at', [$from, $to])->count();
        $couponRate = $totalOrdersInRange > 0 ? round(($ordersWithCoupon / $totalOrdersInRange) * 100, 1) : 0;

        return view('admin.reports.revenue', compact(
            'summary','prevSummary','revenueGrowth','byCategory','byBrand',
            'monthlyTrend','couponImpact','couponRate','from','to'
        ));
    }

    // ── product performance ───────────────────────────────────────────────
    public function products(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        // Top sellers by revenue
        $topByRevenue = OrderItem::select('product_id', 'product_name', DB::raw('SUM(subtotal) as revenue'), DB::raw('SUM(quantity) as qty_sold'), DB::raw('COUNT(DISTINCT order_id) as orders'))
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('product_id', 'product_name')->orderByDesc('revenue')->take(20)->get();

        // Top by quantity
        $topByQty = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as qty_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('product_id', 'product_name')->orderByDesc('qty_sold')->take(10)->get();

        // Most viewed products
        $mostViewed = Product::with('category')
            ->where('is_active', true)->orderByDesc('views')->take(10)->get();

        // Never sold products (active, in stock)
        $neverSold = Product::where('is_active', true)->where('stock', '>', 0)
            ->whereNotIn('id', OrderItem::whereNotNull('product_id')->select('product_id')->distinct())
            ->with('category')->orderByDesc('created_at')->take(10)->get();

        // Products with low stock
        $lowStock = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->whereRaw('stock <= COALESCE(low_stock_threshold, 5)')
            ->with('category')->orderBy('stock')->take(15)->get();

        // Category performance
        $categoryPerf = OrderItem::select('categories.name as category', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty_sold'), DB::raw('COUNT(DISTINCT order_items.order_id) as orders'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('categories.name')->orderByDesc('revenue')->get();

        return view('admin.reports.products', compact(
            'topByRevenue','topByQty','mostViewed','neverSold','lowStock','categoryPerf','from','to'
        ));
    }

    // ── customer analytics ────────────────────────────────────────────────
    public function customers(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        // New customers over time
        $newCustomersTrend = User::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as date'), DB::raw('COUNT(*) as count'))
            ->where('role', '!=', 'admin')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')->orderBy('date')->get();

        // Summary
        $totalCustomers  = User::where('role', '!=', 'admin')->count();
        $newInRange      = User::where('role', '!=', 'admin')->whereBetween('created_at', [$from, $to])->count();
        $activeCustomers = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->distinct('user_id')->count('user_id');

        // Top customers by spend
        $topCustomers = Order::select('user_id', DB::raw('SUM(total) as total_spent'), DB::raw('COUNT(*) as order_count'), DB::raw('AVG(total) as avg_order'), DB::raw('MAX(created_at) as last_order'))
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->whereNotNull('user_id')
            ->groupBy('user_id')->orderByDesc('total_spent')->take(15)->get();

        // Returning vs new buyers
        $returningBuyers = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $firstTimeBuyers = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->count();

        // Customers with no orders
        $customersNoOrders = User::where('role', '!=', 'admin')
            ->whereNotIn('id', Order::select('user_id')->whereNotNull('user_id')->distinct())
            ->count();

        // Average order value by customer segment
        $avgOrderValue = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->avg('total');

        // Orders per customer
        $ordersPerCustomer = $activeCustomers > 0
            ? round(Order::whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->whereNotNull('user_id')->count() / $activeCustomers, 2)
            : 0;

        // Customers by city (shipping_city)
        $byCity = Order::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')
            ->whereNotNull('shipping_city')
            ->select('shipping_city', DB::raw('COUNT(DISTINCT user_id) as customers'), DB::raw('SUM(total) as revenue'))
            ->groupBy('shipping_city')->orderByDesc('revenue')->take(10)->get();

        return view('admin.reports.customers', compact(
            'newCustomersTrend','totalCustomers','newInRange','activeCustomers',
            'topCustomers','returningBuyers','firstTimeBuyers','customersNoOrders',
            'avgOrderValue','ordersPerCustomer','byCity','from','to'
        ));
    }

    // ── inventory report ──────────────────────────────────────────────────
    public function inventory(Request $request)
    {
        // Summary
        $totalProducts   = Product::where('is_active', true)->count();
        $inStockCount    = Product::where('is_active', true)->where('stock', '>', 0)->count();
        $outOfStockCount = Product::where('is_active', true)->where('stock', 0)->count();
        $lowStockCount   = Product::where('is_active', true)->where('stock', '>', 0)
            ->whereRaw('stock <= COALESCE(low_stock_threshold, 5)')->count();
        $totalStockValue = Product::where('is_active', true)
            ->selectRaw('SUM(stock * price) as value')->value('value') ?? 0;

        // Stock by category
        $byCategory = Product::select('categories.name as category', DB::raw('SUM(products.stock) as total_stock'), DB::raw('COUNT(products.id) as product_count'), DB::raw('SUM(products.stock * products.price) as stock_value'))
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.is_active', true)
            ->groupBy('categories.name')->orderByDesc('stock_value')->get();

        // Out of stock products
        $outOfStock = Product::where('is_active', true)->where('stock', 0)
            ->with('category')->orderBy('name')->paginate(15, ['*'], 'oos_page');

        // Low stock products
        $lowStock = Product::where('is_active', true)->where('stock', '>', 0)
            ->whereRaw('stock <= COALESCE(low_stock_threshold, 5)')
            ->with('category')->orderBy('stock')->get();

        // Most sold (30 days) for reorder priority
        $reorderPriority = OrderItem::select('product_id', 'product_name', DB::raw('SUM(quantity) as sold_30d'))
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', Carbon::now()->subDays(30))->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded'))
            ->groupBy('product_id', 'product_name')->orderByDesc('sold_30d')
            ->with(['product:id,stock,price,low_stock_threshold'])
            ->take(15)->get();

        // Inactive products with stock
        $inactiveWithStock = Product::where('is_active', false)->where('stock', '>', 0)
            ->with('category')->orderByDesc('stock')->take(10)->get();

        // Stock distribution (bins)
        $stockBins = [
            '0' => Product::where('is_active', true)->where('stock', 0)->count(),
            '1-10' => Product::where('is_active', true)->whereBetween('stock', [1, 10])->count(),
            '11-50' => Product::where('is_active', true)->whereBetween('stock', [11, 50])->count(),
            '51-100' => Product::where('is_active', true)->whereBetween('stock', [51, 100])->count(),
            '100+' => Product::where('is_active', true)->where('stock', '>', 100)->count(),
        ];

        return view('admin.reports.inventory', compact(
            'totalProducts','inStockCount','outOfStockCount','lowStockCount','totalStockValue',
            'byCategory','outOfStock','lowStock','reorderPriority','inactiveWithStock','stockBins'
        ));
    }

    // ── orders report ────────────────────────────────────────────────────
    public function orders(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $groupBy = $request->get('group_by', 'day');

        $dateFormat = match($groupBy) {
            'month' => '%Y-%m',
            'week'  => '%x-%v',
            default => '%Y-%m-%d',
        };

        $orderTrend = Order::select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing"),
                DB::raw("SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped"),
                DB::raw("SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status='refunded' THEN 1 ELSE 0 END) as refunded"),
                DB::raw('AVG(total) as avg_order_value')
            )
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('period')->orderBy('period')->get();

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status='refunded' THEN 1 ELSE 0 END) as refunded,
                AVG(total) as avg_order_value,
                SUM(total) as total_revenue")
            ->first();

        $byStatus = Order::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('status')->orderByDesc('count')->get();

        $byPayment = Order::whereBetween('created_at', [$from, $to])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')->orderByDesc('count')->get();

        $byCity = Order::whereBetween('created_at', [$from, $to])
            ->select('shipping_city', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('shipping_city')->orderByDesc('count')->take(10)->get();

        $recentPending = Order::where('status', 'pending')
            ->with('user:id,name,email')->latest()->take(10)->get();

        return view('admin.reports.orders', compact(
            'orderTrend','summary','byStatus','byPayment','byCity','recentPending','from','to','groupBy'
        ));
    }

    // ── payments report ───────────────────────────────────────────────────
    public function payments(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as total_orders,
                SUM(total) as total_collected,
                SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN payment_status='pending' THEN total ELSE 0 END) as pending_amount,
                SUM(CASE WHEN payment_status='failed' THEN total ELSE 0 END) as failed_amount,
                SUM(CASE WHEN payment_status='refunded' THEN total ELSE 0 END) as refunded_amount")
            ->first();

        $byMethod = Order::whereBetween('created_at', [$from, $to])
            ->select('payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw("SUM(CASE WHEN payment_status='paid' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN payment_status='pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN payment_status='failed' THEN 1 ELSE 0 END) as failed_count")
            )
            ->groupBy('payment_method')->orderByDesc('revenue')->get();

        $byStatus = Order::whereBetween('created_at', [$from, $to])
            ->select('payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as amount'))
            ->groupBy('payment_status')->orderByDesc('amount')->get();

        $monthlyTrend = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw("SUM(CASE WHEN payment_method='cod' THEN total ELSE 0 END) as cod"),
                DB::raw("SUM(CASE WHEN payment_method='card' THEN total ELSE 0 END) as card"),
                DB::raw("SUM(CASE WHEN payment_method='bkash' THEN total ELSE 0 END) as bkash"),
                DB::raw("SUM(CASE WHEN payment_method='nagad' THEN total ELSE 0 END) as nagad")
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')->orderBy('month')->get();

        $pendingPayments = Order::whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'pending')
            ->with('user:id,name,email')->latest()->take(15)->get();

        $failedPayments = Order::whereBetween('created_at', [$from, $to])
            ->where('payment_status', 'failed')
            ->with('user:id,name,email')->latest()->take(10)->get();

        return view('admin.reports.payments', compact(
            'summary','byMethod','byStatus','monthlyTrend','pendingPayments','failedPayments','from','to'
        ));
    }

    // ── marketing report ──────────────────────────────────────────────────
    public function marketing(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $withCoupon    = Order::whereBetween('created_at', [$from, $to])->whereNotNull('coupon_code')->where('discount', '>', 0);
        $withoutCoupon = Order::whereBetween('created_at', [$from, $to])->where(fn($q) => $q->whereNull('coupon_code')->orWhere('discount', 0));

        $couponSummary = (object)[
            'orders_with_coupon'    => (clone $withCoupon)->count(),
            'orders_without_coupon' => (clone $withoutCoupon)->count(),
            'total_discount'        => (clone $withCoupon)->sum('discount'),
            'avg_discount'          => (clone $withCoupon)->avg('discount') ?? 0,
            'revenue_with_coupon'   => (clone $withCoupon)->sum('total'),
            'revenue_without_coupon'=> (clone $withoutCoupon)->sum('total'),
            'avg_order_with_coupon' => (clone $withCoupon)->avg('total') ?? 0,
            'avg_order_without'     => (clone $withoutCoupon)->avg('total') ?? 0,
        ];

        $totalOrders = Order::whereBetween('created_at', [$from, $to])->count();
        $couponRate  = $totalOrders > 0 ? round(($couponSummary->orders_with_coupon / $totalOrders) * 100, 1) : 0;

        $topCoupons = Order::whereBetween('created_at', [$from, $to])
            ->whereNotNull('coupon_code')->where('discount', '>', 0)
            ->select('coupon_code', DB::raw('COUNT(*) as uses'), DB::raw('SUM(discount) as total_discount'), DB::raw('SUM(total) as revenue'), DB::raw('AVG(discount) as avg_discount'))
            ->groupBy('coupon_code')->orderByDesc('uses')->take(15)->get();

        $discountTrend = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN coupon_code IS NOT NULL AND discount > 0 THEN 1 ELSE 0 END) as coupon_orders"),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('SUM(total) as revenue')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')->orderBy('month')->get();

        return view('admin.reports.marketing', compact(
            'couponSummary','couponRate','topCoupons','discountTrend','totalOrders','from','to'
        ));
    }

    // ── shipping report ───────────────────────────────────────────────────
    public function shipping(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as total_orders,
                SUM(shipping) as total_shipping_revenue,
                AVG(shipping) as avg_shipping,
                SUM(CASE WHEN shipping=0 THEN 1 ELSE 0 END) as free_shipping_count,
                SUM(CASE WHEN shipping>0 THEN 1 ELSE 0 END) as paid_shipping_count,
                SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status='shipped' THEN 1 ELSE 0 END) as shipped_count")
            ->first();

        $deliveryRate = ($summary->total_orders ?? 0) > 0
            ? round(($summary->delivered_count / $summary->total_orders) * 100, 1) : 0;

        $byCity = Order::whereBetween('created_at', [$from, $to])
            ->select('shipping_city',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(shipping) as shipping_revenue'),
                DB::raw("SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled")
            )
            ->groupBy('shipping_city')->orderByDesc('orders')->take(15)->get();

        $byCountry = Order::whereBetween('created_at', [$from, $to])
            ->select('shipping_country', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as revenue'), DB::raw('SUM(shipping) as shipping_revenue'))
            ->groupBy('shipping_country')->orderByDesc('orders')->get();

        $shippingTrend = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(shipping) as shipping_revenue'),
                DB::raw("SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) as delivered")
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')->orderBy('month')->get();

        $freeShippingRevenue = Order::whereBetween('created_at', [$from, $to])
            ->where('shipping', 0)->whereNotIn('status', ['cancelled','refunded'])->where('payment_status', '!=', 'refunded')->sum('total');

        return view('admin.reports.shipping', compact(
            'summary','deliveryRate','byCity','byCountry','shippingTrend','freeShippingRevenue','from','to'
        ));
    }

    // ── returns & refunds report ──────────────────────────────────────────
    public function returns(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $summary = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('cancelled','refunded') THEN 1 ELSE 0 END) as returned_count,
                SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN status='refunded' THEN 1 ELSE 0 END) as refunded_count,
                SUM(CASE WHEN status IN ('cancelled','refunded') THEN total ELSE 0 END) as lost_revenue,
                SUM(CASE WHEN status='cancelled' THEN total ELSE 0 END) as cancelled_revenue,
                SUM(CASE WHEN status='refunded' THEN total ELSE 0 END) as refunded_revenue")
            ->first();

        $returnRate = ($summary->total_orders ?? 0) > 0
            ? round(($summary->returned_count / $summary->total_orders) * 100, 1) : 0;

        $returnTrend = Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN status IN ('cancelled','refunded') THEN 1 ELSE 0 END) as returned"),
                DB::raw("SUM(CASE WHEN status IN ('cancelled','refunded') THEN total ELSE 0 END) as lost_revenue")
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')->orderBy('month')->get();

        $returnsByPayment = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['cancelled','refunded'])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as amount'))
            ->groupBy('payment_method')->orderByDesc('count')->get();

        $returnsByCity = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['cancelled','refunded'])
            ->select('shipping_city', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as amount'))
            ->groupBy('shipping_city')->orderByDesc('count')->take(10)->get();

        $recentReturns = Order::whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['cancelled','refunded'])
            ->with('user:id,name,email')->latest()->take(20)->get();

        return view('admin.reports.returns', compact(
            'summary','returnRate','returnTrend','returnsByPayment','returnsByCity','recentReturns','from','to'
        ));
    }

    // ── excel downloads ───────────────────────────────────────────────────
    public function downloadSales(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $group = $request->get('group_by', 'day');
        $filename = "sales-report-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";
        return Excel::download(new SalesReportExport($from, $to, $group), $filename);
    }

    public function downloadRevenue(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $filename = "revenue-report-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";
        return Excel::download(new RevenueReportExport($from, $to), $filename);
    }

    public function downloadProducts(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $filename = "products-report-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";
        return Excel::download(new ProductsReportExport($from, $to), $filename);
    }

    public function downloadCustomers(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $filename = "customers-report-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";
        return Excel::download(new CustomersReportExport($from, $to), $filename);
    }

    public function downloadInventory()
    {
        $filename = "inventory-report-" . now()->toDateString() . ".xlsx";
        return Excel::download(new InventoryReportExport(), $filename);
    }
}
