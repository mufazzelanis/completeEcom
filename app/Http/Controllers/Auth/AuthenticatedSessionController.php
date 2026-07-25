<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Cart;
use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
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
        $request->authenticate();

        if ($request->session()->has('2fa_pending_user_id')) {
            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $this->mergeGuestCart($request);

        try {
            LoginActivity::record(auth()->id(), $request->userAgent() ?? '', $request->ip());
        } catch (\Throwable $e) {
            Log::error('LoginActivity recording failed: '.$e->getMessage());
        }

        $user = Auth::user();
        if ($user && $user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    private function mergeGuestCart(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        $guestItems = Cart::where('session_id', $sessionId)->get();

        foreach ($guestItems as $guestItem) {
            $existing = Cart::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
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
