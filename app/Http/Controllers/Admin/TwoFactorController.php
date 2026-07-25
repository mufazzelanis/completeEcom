<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class TwoFactorController extends Controller
{
    private const PURPOSE = 'admin_2fa_setup';

    public function __construct(private TwoFactorAuthService $totp)
    {
        //
    }

    /**
     * Reachable by any admin-capable account even before 2FA is confirmed — these routes
     * sit outside the 'admin' middleware group (see AdminMiddleware) deliberately, so
     * enrollment itself is never blocked by the "must have 2FA" check it enforces.
     * The route group only applies 'auth'; this fills the rest of that gap per-request.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->canAccessAdmin(), 403);
    }

    public function show(Request $request): View
    {
        $this->authorizeAdmin($request);
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('admin.two-factor.show', [
                'enabled' => true,
                'recoveryCodesCount' => count($user->two_factor_recovery_codes ?? []),
            ]);
        }

        $code = Otp::generate($user->email, self::PURPOSE);
        $this->sendCode($user->email, $code);

        return view('admin.two-factor.setup', [
            'enabled' => false,
            'email' => $user->email,
            // Never populated in production — see Otp::generate()'s own env guard on
            // otp_code_plain; this is the same email content shown on-screen for local
            // testing where there's no real mail server to check.
            'devCode' => app()->environment('production') ? null : $code,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        if (! Otp::verify($user->email, self::PURPOSE, trim($request->input('code')))) {
            return back()->withErrors(['code' => 'That code did not match or has expired. A fresh code was sent — reload this page and try the new one.']);
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return redirect()->route('admin.two-factor.show')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'Two-factor authentication is now enabled on your account.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = $request->user();
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->route('admin.two-factor.show')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 400);

        $codes = $this->totp->generateRecoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return redirect()->route('admin.two-factor.show')
            ->with('recovery_codes', $codes)
            ->with('success', 'New recovery codes generated — your old codes no longer work.');
    }

    private function sendCode(string $email, string $code): void
    {
        try {
            Mail::raw("Your admin verification code is: {$code}\n\nThis code expires in 5 minutes.", function ($message) use ($email) {
                $message->to($email)->subject('Your admin verification code');
            });
        } catch (Throwable $e) {
            Log::warning('Admin 2FA setup OTP email failed to send: ' . $e->getMessage());
        }
    }
}
