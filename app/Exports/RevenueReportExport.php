<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class RevenueReportExport implements WithMultipleSheets
{
    public function __construct(
        private Carbon $from,
        private Carbon $to
    ) {}

    public function sheets(): array
    {
        return [
            new RevenueMonthlySheet($this->from, $this->to),
            new RevenueCategorySheet($this->from, $this->to),
            new RevenueBrandSheet($this->from, $this->to),
        ];
    }
}

class RevenueMonthlySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private Carbon $from, private Carbon $to) {}
    public function title(): string { return 'Monthly Revenue'; }
    public function headings(): array { return ['Month', 'Orders', 'Gross Revenue ($)', 'Discounts ($)', 'Shipping ($)', 'Tax ($)', 'Net Revenue ($)']; }

    public function collection()
    {
        return Order::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(discount) as discounts'),
                DB::raw('SUM(shipping) as shipping'),
                DB::raw('SUM(tax) as tax')
            )
            ->whereBetween('created_at', [$this->from, $this->to])
            ->whereNotIn('status', ['cancelled','refunded'])
            ->groupBy('month')->orderBy('month')->get()
            ->map(fn($r) => [
                $r->month, $r->orders,
                number_format($r->revenue,2),
                number_format($r->discounts,2),
                number_format($r->shipping,2),
                number_format($r->tax,2),
                number_format($r->revenue - $r->discounts, 2),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]];
    }
}

class RevenueCategorySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private Carbon $from, private Carbon $to) {}
    public function title(): string { return 'By Category'; }
    public function headings(): array { return ['Category', 'Revenue ($)', 'Units Sold', 'Orders']; }

    public function collection()
    {
        return OrderItem::select('categories.name as category', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty'), DB::raw('COUNT(DISTINCT order_items.order_id) as orders'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$this->from, $this->to])->whereNotIn('status', ['cancelled','refunded']))
            ->groupBy('categories.name')->orderByDesc('revenue')->get()
            ->map(fn($r) => [$r->category, number_format($r->revenue,2), $r->qty, $r->orders]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']]]];
    }
}

class RevenueBrandSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private Carbon $from, private Carbon $to) {}
    public function title(): string { return 'By Brand'; }
    public function headings(): array { return ['Brand', 'Revenue ($)', 'Units Sold', 'Orders']; }

    public function collection()
    {
        return OrderItem::select('brands.name as brand', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty'), DB::raw('COUNT(DISTINCT order_items.order_id) as orders'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$this->from, $this->to])->whereNotIn('status', ['cancelled','refunded']))
            ->groupBy('brands.name')->orderByDesc('revenue')->get()
            ->map(fn($r) => [$r->brand, number_format($r->revenue,2), $r->qty, $r->orders]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F59E0B']]]];
    }
}
