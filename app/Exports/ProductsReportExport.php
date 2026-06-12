<?php

namespace App\Exports;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ProductsReportExport implements WithMultipleSheets
{
    public function __construct(private Carbon $from, private Carbon $to) {}

    public function sheets(): array
    {
        return [
            new TopProductsSheet($this->from, $this->to),
            new InventoryStatusSheet(),
        ];
    }
}

class TopProductsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private Carbon $from, private Carbon $to) {}
    public function title(): string { return 'Top Products'; }
    public function headings(): array { return ['Product', 'Units Sold', 'Orders', 'Revenue ($)']; }

    public function collection()
    {
        return OrderItem::select('product_name', DB::raw('SUM(quantity) as qty_sold'), DB::raw('COUNT(DISTINCT order_id) as orders'), DB::raw('SUM(subtotal) as revenue'))
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$this->from, $this->to])->whereNotIn('status', ['cancelled','refunded']))
            ->groupBy('product_name')->orderByDesc('revenue')->get()
            ->map(fn($r) => [$r->product_name, $r->qty_sold, $r->orders, number_format($r->revenue,2)]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']]]];
    }
}

class InventoryStatusSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'Inventory Status'; }
    public function headings(): array { return ['Product', 'SKU', 'Category', 'Price ($)', 'Stock', 'Status', 'Stock Value ($)']; }

    public function collection()
    {
        return Product::with('category')
            ->where('is_active', true)
            ->orderBy('stock')
            ->get()
            ->map(fn($p) => [
                $p->name, $p->sku, $p->category?->name,
                number_format($p->price,2), $p->stock,
                $p->stock === 0 ? 'Out of Stock' : ($p->stock <= ($p->low_stock_threshold ?? 5) ? 'Low Stock' : 'In Stock'),
                number_format($p->stock * $p->price, 2),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]];
    }
}
