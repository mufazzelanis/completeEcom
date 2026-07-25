<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SalesReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RebuildSalesReport extends Command
{
    protected $signature = 'sales-report:rebuild {--from=} {--to=}';

    protected $description = 'Rebuild the daily sales_reports snapshot table from the orders table (backfill or self-heal)';

    public function handle(): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : Carbon::parse(Order::min('created_at') ?? now())->startOfDay();

        $to = $this->option('to')
            ? Carbon::parse($this->option('to'))->startOfDay()
            : Carbon::today();

        $days = $from->diffInDays($to) + 1;
        $this->info("Rebuilding sales_reports for {$days} day(s): {$from->toDateString()} → {$to->toDateString()}");

        $bar = $this->output->createProgressBar($days);
        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            SalesReport::rebuildForDate($date->copy());
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
