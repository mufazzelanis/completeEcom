<x-guest-layout>
    @php
        // "Sell on :site" (header/footer) sends guests here with ?intent=vendor — pre-select
        // the Sell card and, if validation fails, keep the choice via old() like any other field.
        $accountType = old('account_type', request()->query('intent') === 'vendor' ? 'vendor' : 'customer');
    @endphp
    <form method="POST" action="{{ route('register') }}" x-data="{ accountType: '{{ $accountType }}' }">
        @csrf

        <!-- Account type -->
        <div class="mb-5">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('I want to') }}</span>
            <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="{{ __('Account type') }}">
                <label class="relative flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3.5 text-center cursor-pointer transition-colors"
                       :class="accountType === 'customer' ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 ring-1 ring-orange-500' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                    <input type="radio" name="account_type" value="customer" x-model="accountType" class="sr-only">
                    <svg class="w-5 h-5" :class="accountType === 'customer' ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1.35 11.15A2 2 0 0115.66 22H8.34a2 2 0 01-1.99-1.85L5 9z"/></svg>
                    <span class="text-sm font-semibold" :class="accountType === 'customer' ? 'text-orange-700 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'">{{ __('Shop') }}</span>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight">{{ __('Buy products') }}</span>
                </label>
                <label class="relative flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3.5 text-center cursor-pointer transition-colors"
                       :class="accountType === 'vendor' ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 ring-1 ring-orange-500' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                    <input type="radio" name="account_type" value="vendor" x-model="accountType" class="sr-only">
                    <svg class="w-5 h-5" :class="accountType === 'vendor' ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h18v4H3V3zm0 7h18v11H3V10zm4 4h4"/></svg>
                    <span class="text-sm font-semibold" :class="accountType === 'vendor' ? 'text-orange-700 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'">{{ __('Sell') }}</span>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight">{{ __('Become a vendor') }}</span>
                </label>
            </div>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500" x-show="accountType === 'vendor'" x-cloak>
                {{ __("You'll fill out your business details right after this.") }}
            </p>
        </div>

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
                <span x-text="accountType === 'vendor' ? '{{ __('Create seller account') }}' : '{{ __('Register') }}'"></span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
