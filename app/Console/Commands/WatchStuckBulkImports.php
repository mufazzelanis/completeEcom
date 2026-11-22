<?php

namespace App\Console\Commands;

use App\Models\BulkImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class WatchStuckBulkImports extends Command
{
    protected $signature = 'bulk-imports:watch-stuck';

    protected $description = 'Mark bulk imports stuck in "processing" as failed (the worker was killed by its own timeout/OOM/host before it could update its own status)';

    public function handle(): int
    {
        // ImportProductsCsvJob::$timeout is 3600s — a row still "processing" well past
        // that was never going to finish normally. Its own catch/finally never got to
        // run (process killed, not a caught exception), so nothing else marks it failed
        // or cleans up its temp files; without this, the admin's status page just shows
        // "still processing" forever with no error and no way to retry/re-upload safely.
        $stuck = BulkImport::where('status', 'processing')
            ->where('started_at', '<=', now()->subMinutes(65))
            ->get();

        foreach ($stuck as $import) {
            $import->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'errors'      => array_merge($import->errors ?? [], [
                    'Import timed out or the worker process was killed before it could finish. Please re-upload.',
                ]),
            ]);

            // The job's own finally block never ran — clean up what it would have.
            if ($import->stored_path) {
                Storage::disk('local')->delete($import->stored_path);
            }
            if ($import->images_zip_path) {
                Storage::disk('local')->delete($import->images_zip_path);
            }
        }

        if ($stuck->isNotEmpty()) {
            $this->info("Marked {$stuck->count()} stuck bulk import(s) as failed.");
        }

        return self::SUCCESS;
    }
}
