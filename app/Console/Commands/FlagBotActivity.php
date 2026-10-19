<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-checks activity already logged as "real visitor" and marks scrapers as bots — for rows
 * recorded before the browser-header check (ActivityLog::automationSignal) existed, when a
 * fake "Chrome" User-Agent was enough to pass as a person. Judged by behaviour: a guest IP
 * that viewed at least --threshold product pages on one day without ever adding to cart,
 * placing an order or logging in that day is a scraper, not a shopper.
 *
 *   php artisan activity-log:flag-bots --dry-run     (see what would change)
 *   php artisan activity-log:flag-bots
 */
class FlagBotActivity extends Command
{
    protected $signature = 'activity-log:flag-bots
        {--days=60 : How far back to look}
        {--threshold=20 : Product views per IP per day that count as a scraper}
        {--dry-run : Only report}';

    protected $description = 'Mark past activity from scraper IPs (many product views, no cart/order) as bot traffic';

    public function handle(): int
    {
        $since = now()->subDays((int) $this->option('days'));
        $threshold = max(5, (int) $this->option('threshold'));

        // guest IP + day combinations with heavy product viewing
        $heavy = DB::table('activity_logs')
            ->selectRaw('ip_address, DATE(created_at) as day, COUNT(*) as views')
            ->where('event', 'product.view')->whereNull('user_id')->where('is_bot', false)
            ->whereNotNull('ip_address')->where('created_at', '>=', $since)
            ->groupBy('ip_address', DB::raw('DATE(created_at)'))
            ->having('views', '>=', $threshold)
            ->orderByDesc('views')
            ->get();

        $flagged = [];
        foreach ($heavy as $row) {
            $shopped = DB::table('activity_logs')
                ->where('ip_address', $row->ip_address)
                ->whereDate('created_at', $row->day)
                ->whereIn('event', ['cart.add', 'order.place', 'login'])
                ->exists();
            if (!$shopped) {
                $flagged[] = $row;
            }
        }

        $this->table(['IP', 'Day', 'Product views'], collect($flagged)->take(25)->map(fn ($r) => [$r->ip_address, $r->day, $r->views])->all());
        $rows = collect($flagged)->sum('views');
        $this->line(count($flagged) . ' IP/day combinations, ' . number_format($rows) . ' product views' . (count($flagged) > 25 ? ' (first 25 shown)' : ''));

        if ($this->option('dry-run') || !$flagged) {
            $this->info($this->option('dry-run') ? 'Dry run — nothing changed.' : 'Nothing to flag.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($flagged as $row) {
            $updated += DB::table('activity_logs')
                ->where('ip_address', $row->ip_address)->whereDate('created_at', $row->day)
                ->whereNull('user_id')->where('is_bot', false)
                ->update(['is_bot' => true]);
        }
        cache()->forget('activity_log:event_types');
        $this->info("Marked {$updated} activity rows as bot traffic.");

        return self::SUCCESS;
    }
}
