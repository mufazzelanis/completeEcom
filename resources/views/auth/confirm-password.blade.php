<x-guest-layout>
    {{-- Intro text now lives in the shared card header (layouts/guest.blade.php's
         $authHeading/$authSubheading) so it isn't duplicated here. --}}
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <x-auth-field id="password" name="password" type="password" icon="lock" :label="__('Password')"
                      required autocomplete="current-password" />

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
