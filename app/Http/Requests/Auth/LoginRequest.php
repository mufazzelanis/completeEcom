<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->input('email');
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        $user = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = \App\Models\User::where('email', $identifier)->first();
        } else {
            $phone = preg_replace('/[^0-9]/', '', normalize_digits($identifier));
            $user = \App\Models\User::where('phone', $phone)->first();
        }

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($password, $user->getAuthPassword())) {
            RateLimiter::hit($this->throttleKey(), (int) setting('login_lockout_minutes', 15) * 60);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            // Password verified but 2FA still required — stash a short-lived pending-login
            // marker instead of fully authenticating, so the session has no access yet.
            session([
                '2fa_pending_user_id' => $user->id,
                '2fa_pending_remember' => $remember,
            ]);
            RateLimiter::clear($this->throttleKey());
            return;
        }

        \Illuminate\Support\Facades\Auth::login($user, $remember);
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), (int) setting('login_max_attempts', 5))) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
