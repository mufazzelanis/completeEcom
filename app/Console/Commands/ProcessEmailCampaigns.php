<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignSender;
use Illuminate\Console\Command;

class ProcessEmailCampaigns extends Command
{
    protected $signature = 'email-campaigns:process';

    protected $description = 'Start due scheduled campaigns and process a batch of pending sends for campaigns currently sending';

    public function handle(): int
    {
        $due = EmailCampaign::where('status', 'scheduled')->where('scheduled_at', '<=', now())->get();

        foreach ($due as $campaign) {
            EmailCampaignSender::startSending($campaign);
        }

        $sending = EmailCampaign::where('status', 'sending')->get();

        foreach ($sending as $campaign) {
            EmailCampaignSender::processBatch($campaign);
        }

        $this->info('Started ' . $due->count() . ' due campaign(s), processed a batch for ' . $sending->count() . ' sending campaign(s).');

        return self::SUCCESS;
    }
}
