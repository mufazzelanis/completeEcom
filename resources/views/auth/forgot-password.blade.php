<x-guest-layout>
    {{-- Intro text now lives in the shared card header (layouts/guest.blade.php's
         $authHeading/$authSubheading) so it isn't duplicated here. --}}
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <x-auth-field id="email" name="email" type="email" icon="mail" :label="__('Email')"
                      :value="old('email')" required autofocus />

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
