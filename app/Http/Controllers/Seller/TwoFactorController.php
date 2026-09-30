<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * The seller-facing "enable 2FA on my account" flow, reached from the Seller Profile
 * page. Entirely opt-in, same as the customer account version — see that class's
 * docblock for why show() only sends a code when actually landed on.
 */
class TwoFactorController extends Controller
{
    private const PURPOSE = 'seller_2fa_setup';

    public function __construct(private TwoFactorEnrollmentService $enrollment)
    {
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('seller.two-factor.show', [
                'enabled' => true,
                'recoveryCodesCount' => count($user->two_factor_recovery_codes ?? []),
            ]);
        }

        $this->enrollment->issueCodeIfNeeded(
            $user,
            self::PURPOSE,
            $request->boolean('resend'),
            'Your seller account verification code',
            'Use the code below to turn on two-factor authentication for your seller account.',
            'Seller 2FA setup'
        );

        return view('seller.two-factor.setup', [
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

        return redirect()->route('seller.two-factor.show')
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

        return redirect()->route('seller.two-factor.show')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 400);

        $codes = $this->enrollment->regenerateRecoveryCodes($user);

        return redirect()->route('seller.two-factor.show')
            ->with('recovery_codes', $codes)
            ->with('success', 'New recovery codes generated — your old codes no longer work.');
    }
}
