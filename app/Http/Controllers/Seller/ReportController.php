<?php

namespace App\Http\Controllers\Seller;

use App\Exports\VendorSalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\VendorTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
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

    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;
        [$from, $to] = $this->dateRange($request);

        $salesTrend = OrderItem::select(
                DB::raw("DATE_FORMAT(order_items.created_at, '%Y-%m-%d') as period"),
                DB::raw('SUM(order_items.subtotal) as revenue'),
                DB::raw('SUM(order_items.quantity) as qty')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.seller_id', $vendor->id)
            ->whereBetween('order_items.created_at', [$from, $to])
            ->groupBy('period')->orderBy('period')->get();

        $base = VendorTransaction::where('vendor_id', $vendor->id)->whereBetween('created_at', [$from, $to]);
        $summary = [
            'hold'      => (clone $base)->where('status', 'hold')->sum('net_amount'),
            'available' => (clone $base)->where('status', 'available')->sum('net_amount'),
            'paid'      => (clone $base)->where('status', 'paid')->sum('net_amount'),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        return view('seller.reports.index', compact('salesTrend', 'summary', 'from', 'to'));
    }

    public function download(Request $request)
    {
        $vendor = $request->user()->vendor;
        [$from, $to] = $this->dateRange($request);

        $filename = "sales-report-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return Excel::download(new VendorSalesReportExport($vendor->id, $from, $to), $filename);
    }
}
