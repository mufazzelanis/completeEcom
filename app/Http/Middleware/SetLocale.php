<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolves the storefront's active language from ?lang=, then the session,
     * then the admin-configured default — admin panel stays on the app's base
     * locale regardless, since only the storefront exposes a language switcher.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*')) {
            return $next($request);
        }

        $active = Language::active();
        if ($active->isEmpty()) {
            return $next($request);
        }

        $requested = $request->query('lang');
        if ($requested && $active->contains('code', $requested)) {
            session(['locale' => $requested]);
        }

        $code = session('locale');
        if (!$code || !$active->contains('code', $code)) {
            $code = Language::default()?->code ?? $active->first()->code;
        }

        app()->setLocale($code);

        $current = $active->firstWhere('code', $code);
        View::share('currentLanguage', $current);
        View::share('activeLanguages', $active);

        return $next($request);
    }
}
