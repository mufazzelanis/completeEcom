<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryReportExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'Inventory Report'; }

    public function headings(): array
    {
        return ['Product', 'SKU', 'Barcode', 'Category', 'Brand', 'Price (৳)', 'Sale Price (৳)', 'Stock', 'Low Stock Threshold', 'Status', 'Stock Value (৳)', 'Active'];
    }

    public function collection()
    {
        return Product::with(['category', 'brand'])
            ->orderBy('stock')
            ->get()
            ->map(fn($p) => [
                $p->name,
                $p->sku,
                $p->barcode,
                $p->category?->name,
                $p->brand?->name,
                number_format($p->price, 2),
                $p->sale_price ? number_format($p->sale_price, 2) : '',
                $p->stock,
                $p->low_stock_threshold ?? 5,
                $p->stock === 0 ? 'Out of Stock' : ($p->stock <= ($p->low_stock_threshold ?? 5) ? 'Low Stock' : 'In Stock'),
                number_format($p->stock * $p->price, 2),
                $p->is_active ? 'Yes' : 'No',
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]],
        ];
    }
}
