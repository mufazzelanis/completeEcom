<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class PublishScheduledBlogPosts extends Command
{
    protected $signature = 'blog:publish-scheduled';

    protected $description = 'Publish blog posts whose scheduled published_at time has arrived';

    public function handle(): int
    {
        $count = BlogPost::where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("Published {$count} scheduled post(s).");

        return self::SUCCESS;
    }
}
