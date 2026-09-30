<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * The customer-facing "enable 2FA on my account" flow, reached from Account -> Security.
 * Unlike the admin version this is entirely opt-in — nothing forces a customer through
 * it, so show() only ever sends a code when the customer has actually landed here.
 */
class TwoFactorController extends Controller
{
    private const PURPOSE = 'customer_2fa_setup';

    public function __construct(private TwoFactorEnrollmentService $enrollment)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('account.two-factor.show', [
                'enabled' => true,
                'recoveryCodesCount' => count($user->two_factor_recovery_codes ?? []),
            ]);
        }

        $this->enrollment->issueCodeIfNeeded(
            $user,
            self::PURPOSE,
            $request->boolean('resend'),
            'Your account verification code',
            'Use the code below to turn on two-factor authentication for your account.',
            'Customer 2FA setup'
        );

        return view('account.two-factor.setup', [
            'enabled' => false,
            'email' => $user->email,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $recoveryCodes = $this->enrollment->confirm($request->user(), self::PURPOSE, $request->input('code'));

        if (! $recoveryCodes) {
            return back()->withErrors(['code' => 'That code did not match or has expired. A fresh code was sent — reload this page and try the new one.']);
        }

        return redirect()->route('account.two-factor.show')
            ->with('recovery_codes', $recoveryCodes)
            ->with('success', 'Two-factor authentication is now enabled on your account.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $this->enrollment->disable($request->user());

        return redirect()->route('account.two-factor.show')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 400);

        $codes = $this->enrollment->regenerateRecoveryCodes($user);

        return redirect()->route('account.two-factor.show')
            ->with('recovery_codes', $codes)
            ->with('success', 'New recovery codes generated — your old codes no longer work.');
    }
}
