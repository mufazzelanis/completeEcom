<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

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

        // A fresh code every time this page loads (including "resend") — cheap since
        // Otp::generate() invalidates whatever code it's replacing for the same pair.
        $code = Otp::generate($user->email, self::PURPOSE);
        $this->sendCode($user->email, $code);

        return view('auth.two-factor-challenge', [
            'devCode' => app()->environment('production') ? null : $code,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => 'required|string']);

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

        $this->mergeGuestCart($request);

        if ($user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    private function sendCode(string $email, string $code): void
    {
        try {
            Mail::raw("Your login verification code is: {$code}\n\nThis code expires in 5 minutes.", function ($message) use ($email) {
                $message->to($email)->subject('Your login verification code');
            });
        } catch (Throwable $e) {
            Log::warning('Login 2FA OTP email failed to send: ' . $e->getMessage());
        }
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
