<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Otp;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\OtpMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const PURPOSE = 'admin_2fa_login';

    public function create(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('2fa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect()->route('login');
        }

        // A plain page load (first arrival, a refresh, browser back/forward, etc.) reuses
        // the still-valid code instead of silently emailing another one every single hit —
        // only an explicit "Resend code" click (?resend=1) issues a fresh one, and even
        // that is throttled so rapid repeat clicks can't fire off a burst of emails.
        $throttleKey = 'otp-resend-login:' . $user->email;
        $needsFreshCode = ! Otp::activeFor($user->email, self::PURPOSE);
        $wantsResend = $request->boolean('resend') && ! RateLimiter::tooManyAttempts($throttleKey, 1);

        if ($needsFreshCode || $wantsResend) {
            $code = Otp::generate($user->email, self::PURPOSE);
            $this->sendCode($user->email, $code);
            RateLimiter::hit($throttleKey, 30);
        }

        return view('auth.two-factor-challenge', [
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => 'required|string']);

        // Same reasoning as AuthenticatedSessionController::store() — captured before
        // regenerate() so the merge below looks up the ID that actually held the
        // guest's cart/wishlist, not the fresh one regenerate() just swapped in.
        $guestSessionId = $request->session()->getId();

        $user = User::findOrFail($userId);
        $inputCode = trim($request->input('code'));
        $verified = Otp::verify($user->email, self::PURPOSE, $inputCode);

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

        $this->mergeGuestCart($guestSessionId);
        $this->mergeGuestWishlist($guestSessionId);

        if ($user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    private function sendCode(string $email, string $code): void
    {
        OtpMailer::send(
            $email,
            $code,
            'Your login verification code',
            'Use the code below to finish signing in to your account.',
            'Login 2FA'
        );
    }

    /**
     * "jo***@example.com" — enough for the user to recognize which inbox to check
     * without exposing the full address on a page anyone with the URL can load.
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($local, 0, min(2, max(mb_strlen($local) - 1, 1)));

        return $visible . str_repeat('*', max(mb_strlen($local) - mb_strlen($visible), 3)) . ($domain ? '@' . $domain : '');
    }

    private function mergeGuestCart(string $guestSessionId): void
    {
        $userId = Auth::id();

        foreach (Cart::where('session_id', $guestSessionId)->get() as $guestItem) {
            $existing = Cart::where('user_id', $userId)->where('product_id', $guestItem->product_id)->first();
            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    private function mergeGuestWishlist(string $guestSessionId): void
    {
        $userId = Auth::id();

        foreach (Wishlist::where('session_id', $guestSessionId)->get() as $guestItem) {
            $exists = Wishlist::where('user_id', $userId)->where('product_id', $guestItem->product_id)->exists();
            if ($exists) {
                $guestItem->delete();
            } else {
                $guestItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }
    }
}
