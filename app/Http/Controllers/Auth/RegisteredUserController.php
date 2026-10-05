<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        if (request()->filled('ref')) {
            session(['ref_code' => strtoupper(request()->query('ref'))]);
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'account_type' => ['nullable', 'in:customer,vendor'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($refCode = session('ref_code')) {
            $referralCode = ReferralCode::where('code', $refCode)->where('user_id', '!=', $user->id)->first();
            if ($referralCode) {
                $user->update(['referred_by' => $referralCode->user_id]);
                $referralCode->increment('total_uses');
            }
            session()->forget('ref_code');
        }

        event(new Registered($user));

        Auth::login($user);

        // "Sell on :site" sends guests here with ?intent=vendor, carried through the form as
        // account_type — skip straight to the seller application instead of the storefront
        // home, so picking "Sell" doesn't dead-end back on a page about shopping.
        if ($request->input('account_type') === 'vendor') {
            return redirect()->route('vendor.apply')
                ->with('success', 'Welcome to ' . setting('site_name', 'our marketplace') . '! Tell us about your business to start selling.');
        }

        return redirect(route('home', absolute: false));
    }
}
