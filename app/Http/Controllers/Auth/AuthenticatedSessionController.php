<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Cart;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $this->mergeGuestCart($guestSessionId);
        $this->mergeGuestWishlist($guestSessionId);

        $user = Auth::user();
        if ($user && $user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    private function mergeGuestCart(string $guestSessionId): void
    {
        $userId = Auth::id();

        $guestItems = Cart::where('session_id', $guestSessionId)->get();

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
     * Same merge shape as the cart above: a guest-favorited product that the
     * now-logging-in user already had saved is just dropped (no double
     * entry); everything else gets reassigned to the account.
     */
    private function mergeGuestWishlist(string $guestSessionId): void
    {
        $userId = Auth::id();

        $guestItems = Wishlist::where('session_id', $guestSessionId)->get();

        foreach ($guestItems as $guestItem) {
            $exists = Wishlist::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->exists();

            if ($exists) {
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
