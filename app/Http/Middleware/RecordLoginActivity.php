<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;

class RecordLoginActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Record on successful login POST
        if ($request->isMethod('POST') && $response->isRedirect() && auth()->check()) {
            $loginRoutes = ['login', 'auth.login'];
            if (in_array($request->route()?->getName(), $loginRoutes)) {
                LoginActivity::record(auth()->id(), $request->userAgent() ?? '', $request->ip());
            }
        }

        return $response;
    }
}
