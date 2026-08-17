<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <x-auth-field id="email" name="email" type="email" icon="mail" :label="__('Email')"
                      :value="old('email', $request->email)" required autofocus autocomplete="username" />

        <!-- Password -->
        <div class="mt-4">
            <x-auth-field id="password" name="password" type="password" icon="lock" :label="__('Password')"
                          required autocomplete="new-password" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-auth-field id="password_confirmation" name="password_confirmation" type="password" icon="lock" :label="__('Confirm Password')"
                          required autocomplete="new-password" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
