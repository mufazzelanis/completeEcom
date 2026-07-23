<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Enter the 6-digit code from your authenticator app to finish signing in. Lost your device? You can use one of your recovery codes instead.
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <div>
            <x-input-label for="code" value="Authentication Code" />
            <x-text-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Verify
            </button>
        </div>
    </form>
</x-guest-layout>
