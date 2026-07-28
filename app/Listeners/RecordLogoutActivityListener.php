<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class RecordLogoutActivityListener
{
    public function handle(Logout $event): void
    {
        try {
            $user = $event->user;
            if ($user) {
                ActivityLogger::log('logout', "{$user->name} logged out", $user);
            }
        } catch (\Throwable $e) {
            Log::error('LogoutActivity listener failed: ' . $e->getMessage());
        }
    }
}
