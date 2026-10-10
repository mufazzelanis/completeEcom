<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\MergesGuestSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use MergesGuestSession;

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Captured before authenticate()/regenerate() touch the session — regenerating
        // swaps in a brand new session ID immediately (Session::migrate() calls setId()
        // in-memory), so reading it any later here would look up the guest's cart and
        // wishlist under an ID that never held them, silently merging nothing.
        $guestSessionId = $request->session()->getId();

        $request->authenticate();

        if ($request->session()->has('2fa_pending_user_id')) {
            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $this->mergeGuestSession($guestSessionId);

        $user = Auth::user();
        if ($user && $user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
