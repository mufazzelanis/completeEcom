<?php

namespace App\Console\Commands;

use App\Support\VisitorIntel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Looks up the network behind recent visitor IPs (ISP / company / data centre, via ip-api.com) so
 * Blog analytics and Activity Log can name them — "Amazon AWS server", "Grameenphone", a fake
 * Googlebot. Runs every minute in the background; pages only ever read the cache.
 * ip-api.com's free batch API allows 15 requests (1,500 IPs) a minute — this uses at most 5.
 */
class ResolveVisitorIps extends Command
{
    protected $signature = 'visitors:resolve-ips {--hours=2 : Also pick up IPs seen in this many past hours}';

    protected $description = 'Look up the network (ISP, company, data centre) of recent visitor IPs';

    public function handle(): int
    {
        $since = now()->subHours(max(1, (int) $this->option('hours')));
        $recent = DB::table('blog_reads')->where('created_at', '>=', $since)->whereNotNull('ip')->distinct()->limit(2000)->pluck('ip')
            ->merge(DB::table('activity_logs')->where('created_at', '>=', $since)->whereNull('user_id')->whereNotNull('ip_address')->distinct()->limit(2000)->pluck('ip_address'))
            ->unique()->values();

        VisitorIntel::ips($recent); // queues the ones not cached yet
        $n = VisitorIntel::resolveQueued(5);
        $this->info("{$n} IPs looked up.");

        return self::SUCCESS;
    }
}
