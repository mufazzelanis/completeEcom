<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ match(true) {
            request()->routeIs('register') => 'Register',
            request()->routeIs('login') => 'Login',
            request()->routeIs('password.request') => 'Forgot Password',
            request()->routeIs('password.reset') => 'Reset Password',
            request()->routeIs('verification.notice') => 'Verify Email',
            request()->routeIs('password.confirm') => 'Confirm Password',
            default => 'Account',
        } }} | {{ setting('site_name', 'ShopVista') }}</title>
        @if($faviconUrl = setting_file_url('favicon'))<link rel="icon" href="{{ $faviconUrl }}">@endif

        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-6 text-center">
                @php $logoUrl = setting_file_url('login_logo', setting_file_url('site_logo')); @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ setting('site_name','ShopVista') }}" class="h-12 mx-auto mb-3">
                @else
                    <div class="w-14 h-14 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <span class="text-white font-bold text-2xl">{{ strtoupper(substr(setting('site_name','S'),0,1)) }}</span>
                    </div>
                @endif
                <h1 class="text-xl font-bold text-gray-800">{{ setting('site_name', 'ShopVista') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ setting('site_tagline', 'Your one-stop shop for everything you need.') }}</p>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-md sm:rounded-xl">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ setting('site_name', 'ShopVista') }}. All rights reserved.
            </p>
        </div>
    </body>
</html>