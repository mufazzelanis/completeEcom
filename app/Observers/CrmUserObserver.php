<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Crm\CrmContacts;
use Illuminate\Support\Facades\Log;
use Throwable;

/** New/updated customer accounts become (or refresh) their CRM contact. Staff accounts are not customers. */
class CrmUserObserver
{
    public bool $afterCommit = true;

    public function created(User $user): void
    {
        $this->sync($user);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged(['name', 'email', 'phone'])) {
            $this->sync($user);
        }
    }

    private function sync(User $user): void
    {
        if ($user->role !== 'customer') {
            return;
        }
        try {
            app(CrmContacts::class)->forUser($user);
        } catch (Throwable $e) {
            Log::warning('CRM user sync failed: ' . $e->getMessage());
        }
    }
}
