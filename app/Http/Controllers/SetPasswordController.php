<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Lets a customer whose account was just created at checkout (phone number only — it gets a
 * random password nobody knows) choose their own password, right on the order-success page.
 * Without this, the "next time just log in with your phone number" promise couldn't be kept:
 * there's no email to reset through, so once the 2-hour session ended they were locked out of
 * their orders (and of reviewing what they bought).
 *
 * Only allowed in the same session that created the account (session('can_set_password')),
 * so it can never be used to change an existing account's password without the current one.
 */
class SetPasswordController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(session('can_set_password') === $request->user()->id, 403);

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->forceFill(['password' => Hash::make($request->password)])->save();
        session()->forget('can_set_password');

        return back()->with('password_set', true);
    }
}
