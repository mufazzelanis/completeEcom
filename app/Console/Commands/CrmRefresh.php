<?php

namespace App\Console\Commands;

use App\Models\Crm\CrmSegment;
use App\Models\Crm\CrmTask;
use App\Services\AdminAlerts\AdminAlerts;
use App\Services\Crm\CrmSegmentEngine;
use App\Services\Crm\CrmSync;
use Illuminate\Console\Command;

class CrmRefresh extends Command
{
    protected $signature = 'crm:refresh {--no-tasks : Skip creating automatic win-back tasks} {--reminders : Only send due-task reminders (no recompute)}';
    protected $description = 'Backfill CRM contacts, recompute customer metrics/RFM, recount segments and create win-back tasks';

    public function handle(CrmSync $sync, CrmSegmentEngine $engine): int
    {
        if (! $this->option('reminders')) {
            $this->recompute($sync, $engine);
        }

        $this->remind();

        return self::SUCCESS;
    }

    private function recompute(CrmSync $sync, CrmSegmentEngine $engine): void
    {
        $stats = $sync->run();
        $this->info("Contacts linked — orders: {$stats['orders']}, new from accounts: {$stats['users']}, new from newsletter: {$stats['subscribers']}; metrics refreshed: {$stats['refreshed']}");

        foreach (CrmSegment::all() as $segment) {
            $engine->recount($segment);
        }

        if (! $this->option('no-tasks')) {
            $this->info('Win-back tasks created: ' . $sync->createWinBackTasks());
        }
    }

    private function remind(): void
    {
        // Tell each assignee once about tasks that just became due.
        $due = CrmTask::open()->whereNotNull('due_at')->where('due_at', '<=', now())->whereNull('reminded_at')->with(['contact', 'assignee'])->limit(100)->get();
        foreach ($due as $task) {
            $task->forceFill(['reminded_at' => now()])->saveQuietly();
            AdminAlerts::notify(
                type: 'crm_task',
                title: 'CRM task due: ' . $task->title,
                body: $task->contact?->name ?: 'General task',
                url: route('admin.crm.tasks.index'),
                data: ['task_id' => $task->id],
                onlyTo: $task->assigned_to && $task->assignee?->is_active ? collect([$task->assignee]) : null,
            );
        }
    }
}
