<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthService $totp): RedirectResponse
    {
        $userId = $request->session()->get('2fa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => 'required|string']);

        $user = User::findOrFail($userId);
        $inputCode = trim($request->input('code'));
        $verified = $totp->verify($user->two_factor_secret, $inputCode);

        if (! $verified) {
            // Fall back to a one-time recovery code — case-insensitive, consumed on use.
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];
            $normalized = strtolower($inputCode);
            if (in_array($normalized, $recoveryCodes, true)) {
                $verified = true;
                $user->two_factor_recovery_codes = array_values(array_diff($recoveryCodes, [$normalized]));
                $user->save();
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'That code was invalid or has expired.']);
        }

        $remember = (bool) $request->session()->get('2fa_pending_remember', false);
        $request->session()->forget(['2fa_pending_user_id', '2fa_pending_remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->mergeGuestCart($request);

        if ($user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    private function mergeGuestCart(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        foreach (Cart::where('session_id', $sessionId)->get() as $guestItem) {
            $existing = Cart::where('user_id', $userId)->where('product_id', $guestItem->product_id)->first();
            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }
}
