<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CustomersReportExport implements WithMultipleSheets
{
    public function __construct(private Carbon $from, private Carbon $to) {}

    public function sheets(): array
    {
        return [
            new TopCustomersSheet($this->from, $this->to),
            new AllCustomersSheet(),
        ];
    }
}

class TopCustomersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private Carbon $from, private Carbon $to) {}
    public function title(): string { return 'Top Customers'; }
    public function headings(): array { return ['Name', 'Email', 'Orders', 'Total Spent ($)', 'Avg Order ($)', 'Last Order']; }

    public function collection()
    {
        return Order::select('user_id', DB::raw('SUM(total) as total_spent'), DB::raw('COUNT(*) as order_count'), DB::raw('AVG(total) as avg_order'), DB::raw('MAX(created_at) as last_order'))
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->whereNotIn('status', ['cancelled','refunded'])
            ->whereNotNull('user_id')
            ->groupBy('user_id')->orderByDesc('total_spent')->get()
            ->map(fn($r) => [
                $r->user?->name, $r->user?->email,
                $r->order_count,
                number_format($r->total_spent,2),
                number_format($r->avg_order,2),
                \Carbon\Carbon::parse($r->last_order)->format('Y-m-d'),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '8B5CF6']]]];
    }
}

class AllCustomersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function title(): string { return 'All Customers'; }
    public function headings(): array { return ['Name', 'Email', 'Registered', 'Total Orders', 'Total Spent ($)']; }

    public function collection()
    {
        return User::where('role', '!=', 'admin')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->orderByDesc('orders_sum_total')
            ->get()
            ->map(fn($u) => [
                $u->name, $u->email,
                $u->created_at->format('Y-m-d'),
                $u->orders_count,
                number_format($u->orders_sum_total ?? 0, 2),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']]]];
    }
}
