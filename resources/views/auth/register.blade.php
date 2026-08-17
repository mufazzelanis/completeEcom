<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <x-auth-field id="name" name="name" icon="user" :label="__('Name')"
                      :value="old('name')" required autofocus autocomplete="name" />

        <!-- Email Address -->
        <div class="mt-4">
            <x-auth-field id="email" name="email" type="email" icon="mail" :label="__('Email')"
                          :value="old('email')" required autocomplete="username" />
        </div>

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
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
