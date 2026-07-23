<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
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

        $secret = $request->session()->get('2fa_setup_secret');
        if (! $secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        $uri = $this->totp->otpAuthUri($secret, $user->email, setting('site_name', 'ShopVista'));

        return view('admin.two-factor.setup', [
            'enabled' => false,
            'secret' => $secret,
            'uri' => $uri,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $request->validate(['code' => 'required|string']);

        $secret = $request->session()->get('2fa_setup_secret');
        if (! $secret) {
            return redirect()->route('admin.two-factor.show')->with('error', 'Setup expired — please scan the QR code again.');
        }

        if (! $this->totp->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code did not match. Please try again.']);
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes();

        $user = $request->user();
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->forget('2fa_setup_secret');

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
        $user->two_factor_secret = null;
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
}
