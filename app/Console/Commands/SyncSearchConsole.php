<?php

namespace App\Console\Commands;

use App\Services\SearchConsole;
use Illuminate\Console\Command;

/**
 * Pulls Google Search Console data for Admin → SEO Insights. Scheduled daily (bootstrap/app.php);
 * re-reads the last few days because Google keeps updating them for a while.
 *
 *   php artisan seo:search-console-sync            (last 5 days)
 *   php artisan seo:search-console-sync --days=90  (first-time backfill)
 */
class SyncSearchConsole extends Command
{
    protected $signature = 'seo:search-console-sync {--days=5 : How many days back to (re)load}';

    protected $description = 'Sync Google Search Console queries/pages/positions into SEO Insights';

    public function handle(): int
    {
        if (! SearchConsole::connected()) {
            $this->warn('Search Console is not connected (Admin → SEO Insights).');

            return self::SUCCESS;
        }
        $days = max(1, min(480, (int) $this->option('days')));
        $to = now()->subDay();
        $from = $to->copy()->subDays($days - 1);

        try {
            $n = SearchConsole::sync($from, $to);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("Stored {$n} rows for {$from->toDateString()} → {$to->toDateString()}.");

        return self::SUCCESS;
    }
}
