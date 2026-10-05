<x-guest-layout>
    @php
        // "Sell on :site" (header/footer) sends guests here with ?intent=vendor — pre-select
        // the Sell card and, if validation fails, keep the choice via old() like any other field.
        $accountType = old('account_type', request()->query('intent') === 'vendor' ? 'vendor' : 'customer');
    @endphp
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data"
          x-data="{ accountType: '{{ $accountType }}', docType: '{{ old('document_type', 'nid') }}' }">
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

        {{-- Seller application — same fields/validation an already-logged-in customer fills in on
             the "Become a Seller" page (resources/views/vendor-registration/create.blade.php), just
             folded into registration itself so a new vendor doesn't have to fill two forms back to
             back. Submitted together with the account fields above; RegisteredUserController creates
             the pending Vendor row right after the user row when account_type is "vendor". --}}
        <div x-show="accountType === 'vendor'" x-cloak class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 space-y-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ __('Seller details') }}</p>

            <div>
                <x-input-label value="{{ __('Business Name') }}" /> <span class="text-red-500">*</span>
                <x-text-input name="business_name" :value="old('business_name')" class="w-full mt-1.5" />
                <x-input-error :messages="$errors->get('business_name')" class="mt-1.5" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label value="{{ __('Phone') }}" />
                    <x-text-input name="phone" :value="old('phone')" class="w-full mt-1.5" />
                </div>
                <div>
                    <x-input-label value="{{ __('Business Email') }}" />
                    <x-text-input type="email" name="business_email" :value="old('business_email')" class="w-full mt-1.5" />
                    <x-input-error :messages="$errors->get('business_email')" class="mt-1.5" />
                </div>
            </div>

            <div>
                <x-input-label value="{{ __('Website (if any)') }}" />
                <x-text-input type="url" name="website" :value="old('website')" placeholder="https://yourshop.com" class="w-full mt-1.5" />
                <x-input-error :messages="$errors->get('website')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label value="{{ __('Tell us about your business') }}" />
                <textarea name="description" rows="3" maxlength="2000"
                    placeholder="{{ __('What do you sell? Where are your products made or sourced from?') }}"
                    class="w-full mt-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2.5 text-sm placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm outline-none transition focus:border-orange-500 dark:focus:border-orange-600 focus:ring-4 focus:ring-orange-500/10 dark:focus:ring-orange-600/20 resize-none">{{ old('description') }}</textarea>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                <x-input-label value="{{ __('Identity Verification') }}" /> <span class="text-red-500">*</span>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 mb-3">{{ __("We need one of these to verify your identity before approving your seller account.") }}</p>

                <div class="flex gap-2 mb-4">
                    <label class="flex-1 flex items-center justify-center gap-2 border rounded-xl px-4 py-2.5 text-sm cursor-pointer transition-colors"
                           :class="docType === 'nid' ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 font-medium' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'">
                        <input type="radio" name="document_type" value="nid" x-model="docType" class="sr-only">
                        {{ __('National ID (NID)') }}
                    </label>
                    <label class="flex-1 flex items-center justify-center gap-2 border rounded-xl px-4 py-2.5 text-sm cursor-pointer transition-colors"
                           :class="docType === 'birth_certificate' ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-400 font-medium' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'">
                        <input type="radio" name="document_type" value="birth_certificate" x-model="docType" class="sr-only">
                        {{ __('Birth Certificate') }}
                    </label>
                </div>
                <x-input-error :messages="$errors->get('document_type')" class="mb-3" />

                <div x-show="docType === 'nid'" x-cloak class="space-y-4">
                    <div>
                        <x-input-label value="{{ __('NID Number') }}" />
                        <x-text-input name="nid_number" :value="old('nid_number')" class="w-full mt-1.5" />
                        <x-input-error :messages="$errors->get('nid_number')" class="mt-1.5" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="{{ __('NID Front Side') }}" />
                            <input type="file" name="nid_front_image" accept="image/*"
                                class="w-full mt-1.5 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 dark:file:bg-orange-500/10 file:text-orange-700 dark:file:text-orange-400">
                            <x-input-error :messages="$errors->get('nid_front_image')" class="mt-1.5" />
                        </div>
                        <div>
                            <x-input-label value="{{ __('NID Back Side') }}" />
                            <input type="file" name="nid_back_image" accept="image/*"
                                class="w-full mt-1.5 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 dark:file:bg-orange-500/10 file:text-orange-700 dark:file:text-orange-400">
                            <x-input-error :messages="$errors->get('nid_back_image')" class="mt-1.5" />
                        </div>
                    </div>
                </div>

                <div x-show="docType === 'birth_certificate'" x-cloak>
                    <x-input-label value="{{ __('Birth Certificate Image') }}" />
                    <input type="file" name="birth_certificate_image" accept="image/*"
                        class="w-full mt-1.5 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 dark:file:bg-orange-500/10 file:text-orange-700 dark:file:text-orange-400">
                    <x-input-error :messages="$errors->get('birth_certificate_image')" class="mt-1.5" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                <span x-text="accountType === 'vendor' ? '{{ __('Submit seller application') }}' : '{{ __('Register') }}'"></span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
