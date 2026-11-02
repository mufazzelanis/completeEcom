<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\LiveVisitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Second pass over guest product views: catches scrapers the request-time checks miss.
 *
 * ActivityLogger flags a view as a bot from its User-Agent, missing browser headers, or more
 * than 30 views from one IP in 10 minutes — so a scraper that claims to be Chrome and fetches
 * slowly (or the first 30 of a fast one's views) still counted as a person. Every real browser
 * runs the page's JavaScript, which pings /live/ping on load (partials/tracking/live-heartbeat);
 * plain HTTP scrapers never do. An IP with several product views and not one ping since is
 * relabelled bot ("no-js"). IPs that added to cart, ordered or logged in are never touched.
 */
class ClassifyBotActivity extends Command
{
    protected $signature = 'activity:classify-bots
        {--hours=3 : How far back to re-check (live pings are kept 2 days, so at most 47)}
        {--report : List the busiest guest IPs of the last 24 hours and their verdict}
        {--shared-ip= : Clear the "too-fast" bot label from this IP — one address used by many visitors at once (a proxy) trips the rate rule}';

    protected $description = 'Relabel guest product views from IPs that never ran the page JavaScript as bot traffic';

    private const MIN_VIEWS = 4;      // a single quick look without a ping isn't evidence
    private const SETTLE_MINUTES = 10; // give a fresh page view time to send its first ping

    public function handle(): int
    {
        if ($this->option('report')) {
            return $this->report();
        }
        if ($ip = $this->option('shared-ip')) {
            // only the rate rule is undone — rows flagged for a bot User-Agent or missing browser headers stay bots
            $n = ActivityLog::where('ip_address', $ip)->where('is_bot', true)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.bot')) = 'too-fast'")
                ->update(['is_bot' => false, 'properties' => DB::raw("JSON_REMOVE(properties, '$.bot')")]);
            $this->info("{$ip}: {$n} views were marked bot only for speed — counted as people again.");

            return self::SUCCESS;
        }

        $from = now()->subHours(min(47, max(1, (int) $this->option('hours'))));
        $to = now()->subMinutes(self::SETTLE_MINUTES);

        $candidates = ActivityLog::query()
            ->whereNull('user_id')->where('is_bot', false)->where('event', 'product.view')
            ->whereBetween('created_at', [$from, $to])->whereNotNull('ip_address')
            ->selectRaw('ip_address, COUNT(*) as views, MIN(created_at) as first_at')
            ->groupBy('ip_address')->havingRaw('COUNT(*) >= ?', [self::MIN_VIEWS])
            ->get();

        $ips = 0;
        $rows = 0;
        foreach ($candidates as $c) {
            if ($this->ranJavaScript($c->ip_address, $c->first_at) || $this->didShop($c->ip_address, $from)) {
                continue;
            }
            $rows += ActivityLog::query()
                ->whereNull('user_id')->where('is_bot', false)->where('event', 'product.view')
                ->where('ip_address', $c->ip_address)->whereBetween('created_at', [$from, $to])
                ->update([
                    'is_bot' => true,
                    'properties' => DB::raw("JSON_SET(COALESCE(properties, '{}'), '$.bot', 'no-js')"),
                ]);
            $ips++;
        }

        $this->info("Checked {$candidates->count()} busy guest IPs — relabelled {$rows} views from {$ips} IPs as bot (no JavaScript).");

        return self::SUCCESS;
    }

    private function ranJavaScript(string $ip, $since): bool
    {
        return LiveVisitor::where('ip_address', $ip)
            ->where('last_seen_at', '>=', \Illuminate\Support\Carbon::parse($since)->subMinutes(2))
            ->exists();
    }

    /** Added to cart, ordered or logged in from this IP — a customer, whatever else it looks like. */
    private function didShop(string $ip, $since): bool
    {
        return ActivityLog::where('ip_address', $ip)->where('created_at', '>=', $since)
            ->whereIn('event', ['cart.add', 'login', 'order.view', 'order.place'])->exists();
    }

    private function report(): int
    {
        $since = now()->subDay();
        $top = ActivityLog::query()
            ->where('event', 'product.view')->whereNull('user_id')->where('created_at', '>=', $since)
            ->selectRaw('ip_address, COUNT(*) as views, SUM(is_bot) as bot, MIN(created_at) as first_at, MAX(created_at) as last_at, MAX(user_agent) as ua')
            ->groupBy('ip_address')->orderByDesc('views')->limit(25)->get();

        $tz = setting('timezone', 'Asia/Dhaka');
        $this->table(['IP', 'views (24h)', 'marked bot', 'last view', 'ran JS', 'verdict', 'user agent'], $top->map(fn ($r) => [
            $r->ip_address,
            $r->views,
            (int) $r->bot,
            \Illuminate\Support\Carbon::parse($r->last_at)->timezone($tz)->format('d M H:i'),
            $this->ranJavaScript((string) $r->ip_address, $r->first_at) ? 'yes' : 'NO',
            (int) $r->bot === (int) $r->views ? 'bot' : ((int) $r->bot === 0 ? 'person' : 'mixed'),
            mb_strimwidth((string) $r->ua, 0, 60, '…'),
        ])->all());

        $all = ActivityLog::where('event', 'product.view')->whereNull('user_id')->where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) as views, SUM(is_bot) as bot, COUNT(DISTINCT ip_address) as ips')->first();
        $this->line(sprintf('Last 24h guest product views: %d (%d bot, %d%%) from %d IPs.', $all->views, $all->bot, $all->views ? round($all->bot / $all->views * 100) : 0, $all->ips));

        return self::SUCCESS;
    }
}
