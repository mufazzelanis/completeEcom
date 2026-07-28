<?php

namespace App\Exports;

use App\Models\VendorTransaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorSalesReportExport implements WithMultipleSheets
{
    public function __construct(
        private int $vendorId,
        private Carbon $from,
        private Carbon $to
    ) {}

    public function sheets(): array
    {
        return [
            new VendorOrderLinesSheet($this->vendorId, $this->from, $this->to),
            new VendorEarningsSummarySheet($this->vendorId, $this->from, $this->to),
        ];
    }
}

class VendorOrderLinesSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private int $vendorId, private Carbon $from, private Carbon $to) {}

    public function title(): string { return 'Order Lines'; }

    public function headings(): array
    {
        return ['Order #', 'Date', 'Product', 'Sale Amount (৳)', 'Commission (৳)', 'Net Earning (৳)', 'Status'];
    }

    public function collection()
    {
        return VendorTransaction::with(['order', 'orderItem'])
            ->where('vendor_id', $this->vendorId)
            ->whereBetween('created_at', [$this->from, $this->to])
            ->latest()
            ->get()
            ->map(fn ($t) => [
                $t->order->order_number ?? $t->order_id,
                $t->created_at->format('Y-m-d'),
                $t->orderItem->product_name ?? '—',
                number_format($t->sale_amount, 2),
                number_format($t->commission_amount, 2),
                number_format($t->net_amount, 2),
                ucfirst($t->status),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']]]];
    }
}

class VendorEarningsSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private int $vendorId, private Carbon $from, private Carbon $to) {}

    public function title(): string { return 'Summary'; }

    public function headings(): array
    {
        return ['Status', 'Line Items', 'Net Amount (৳)'];
    }

    public function collection()
    {
        $base = VendorTransaction::where('vendor_id', $this->vendorId)
            ->whereBetween('created_at', [$this->from, $this->to]);

        return collect(['hold', 'available', 'paid', 'cancelled'])->map(fn ($status) => [
            ucfirst($status),
            (clone $base)->where('status', $status)->count(),
            number_format((clone $base)->where('status', $status)->sum('net_amount'), 2),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '10B981']]]];
    }
}
