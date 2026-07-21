<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesReportExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private string $groupBy = 'day'
    ) {}

    public function title(): string { return 'Sales Report'; }

    public function headings(): array
    {
        return ['Period', 'Orders', 'Revenue (৳)', 'Discounts (৳)', 'Shipping (৳)', 'Avg Order Value (৳)'];
    }

    public function collection()
    {
        $fmt = match($this->groupBy) {
            'month' => '%Y-%m',
            'week'  => '%x-%v',
            default => '%Y-%m-%d',
        };

        return Order::select(
                DB::raw("DATE_FORMAT(created_at, '$fmt') as period"),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(discount) as discounts'),
                DB::raw('SUM(shipping) as shipping'),
                DB::raw('AVG(total) as avg_order')
            )
            ->whereBetween('created_at', [$this->from, $this->to])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('payment_status', '!=', 'refunded')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($r) => [
                $r->period,
                $r->orders,
                number_format($r->revenue, 2),
                number_format($r->discounts, 2),
                number_format($r->shipping, 2),
                number_format($r->avg_order, 2),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
