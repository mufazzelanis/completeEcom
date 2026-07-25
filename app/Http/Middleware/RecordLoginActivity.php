<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordLoginActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if ($request->isMethod('POST') && $response->isRedirect() && auth()->check()) {
                $loginRoutes = ['login', 'login.post', 'auth.login'];
                if (in_array($request->route()?->getName(), $loginRoutes)) {
                    LoginActivity::record(auth()->id(), $request->userAgent() ?? '', $request->ip());
                }
            }
        } catch (\Throwable $e) {
            Log::error('RecordLoginActivity middleware failed: '.$e->getMessage());
        }

        return $response;
    }
}
