<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class RecordLoginActivityListener
{
    public function handle(Login $event): void
    {
        try {
            $user = $event->user;
            if ($user) {
                LoginActivity::record(
                    $user->id,
                    Request::userAgent() ?? '',
                    Request::ip()
                );
            }
        } catch (\Throwable $e) {
            Log::error('LoginActivity listener failed: ' . $e->getMessage());
        }
    }
}
