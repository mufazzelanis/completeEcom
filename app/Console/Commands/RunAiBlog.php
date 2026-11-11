<?php

namespace App\Console\Commands;

use App\Services\AiBlog\Autopilot;
use Illuminate\Console\Command;

class RunAiBlog extends Command
{
    protected $signature = 'ai-blog:run';

    protected $description = 'AI Blog autopilot: write the next queued post when a posting time comes (or "Write now" was pressed)';

    public function handle(): int
    {
        $this->info((new Autopilot())->tick());

        return self::SUCCESS;
    }
}
