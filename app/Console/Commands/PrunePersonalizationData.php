<?php

namespace App\Console\Commands;

use App\Models\ProductView;
use App\Models\SearchQuery;
use Illuminate\Console\Command;

/**
 * Keeps product_views/search_queries (the signal behind the homepage personalized home
 * section — see HomeSection::buildPersonalizedProducts()) from growing forever. 90 days is
 * generous for "recent interest" purposes — the personalized feed only ever reads the most
 * recent 30 views / 10 searches per visitor anyway, so nothing past that window is doing
 * anything useful sitting in the table, just taking up space and ageing-out personal
 * browsing data longer than the feature actually needs it kept.
 */
class PrunePersonalizationData extends Command
{
    protected $signature = 'personalization:prune {--days=90}';

    protected $description = 'Delete product view/search history older than N days (default 90)';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $views = ProductView::where('viewed_at', '<', $cutoff)->delete();
        $searches = SearchQuery::where('searched_at', '<', $cutoff)->delete();

        $this->info("Pruned {$views} product view(s) and {$searches} search quer(y/ies) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
