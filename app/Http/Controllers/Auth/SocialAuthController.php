<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\MergesGuestSession;
use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Continue with Google/Facebook/LinkedIn" — Google and Facebook are built into Socialite
 * itself; LinkedIn is added by the socialiteproviders/linkedin package, registered as a
 * driver in AppServiceProvider::boot(). All three share this one controller since the
 * find-or-create-or-link logic is identical regardless of provider.
 *
 * No 2FA challenge here even if the account has it enabled — the provider itself (Google/
 * Facebook/LinkedIn's own login) already is the strong auth factor, the same assumption
 * virtually every site with both password and social login makes.
 */
class SocialAuthController extends Controller
{
    use MergesGuestSession;

    private const PROVIDERS = ['google', 'facebook', 'linkedin'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        abort_unless($this->providerConfigured($provider), 404);

        // Same session key RegisteredUserController reads — a visitor following a referral
        // link who then signs up via Google shouldn't lose the referral just because they
        // took the social path instead of the form.
        if (request()->filled('ref')) {
            session(['ref_code' => strtoupper(request()->query('ref'))]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        abort_unless($this->providerConfigured($provider), 404);

        // Captured before Auth::login()/regenerate() touch the session — see the identical
        // comment in AuthenticatedSessionController::store() for why the ordering matters.
        $guestSessionId = request()->session()->getId();

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Could not sign in with ' . ucfirst($provider) . '. Please try again.');
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->with(
                'error',
                ucfirst($provider) . " didn't share an email address, so an account can't be created. Please try another sign-in method."
            );
        }

        $idColumn = $provider . '_id';
        $user = User::where($idColumn, $socialUser->getId())->first();

        if (! $user) {
            // An account with this email already exists (signed up with a password, or
            // linked a different provider) — link this provider to it rather than create
            // a second, duplicate account under the same address.
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([$idColumn => $socialUser->getId()]);
            } else {
                $user = User::create([
                    'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: explode('@', $socialUser->getEmail())[0],
                    'email' => $socialUser->getEmail(),
                    // Never used to sign in (this account only authenticates via $provider
                    // until the owner sets a password from Account Settings) — a random
                    // hash rather than a nullable column, so nothing else that assumes
                    // every user has a password needs touching.
                    'password' => Hash::make(Str::random(40)),
                    'email_verified_at' => now(), // the provider already verified it
                    $idColumn => $socialUser->getId(),
                    'avatar_url' => $socialUser->getAvatar(),
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
            }
        }

        if (! $user->avatar_url && $socialUser->getAvatar()) {
            $user->update(['avatar_url' => $socialUser->getAvatar()]);
        }

        // Read by RecordLoginActivityListener (fired synchronously inside Auth::login()
        // below, before regenerate() even runs) so the activity log can say exactly which
        // provider + which provider-side email was used ("logged in with Google
        // (name@gmail.com)") instead of a generic "logged in" indistinguishable from a
        // plain password login.
        request()->session()->put('_login_via', [
            'provider' => $provider,
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
        ]);

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        $this->mergeGuestSession($guestSessionId);

        if ($user->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * An admin who hasn't pasted in credentials yet shouldn't be able to hit a route
     * that then crashes on Socialite's own "client_id missing" exception — this turns
     * that into a plain 404, same as the button simply not existing (see auth/login.blade.php,
     * which only renders a provider's button when this is also true).
     */
    private function providerConfigured(string $provider): bool
    {
        if (setting("{$provider}_login_enabled", '1') !== '1') {
            return false;
        }

        return filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret"));
    }
}
