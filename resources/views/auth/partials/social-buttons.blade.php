{{-- Shared by auth/login.blade.php and auth/register.blade.php — a provider's button only
     renders once it's both switched on (Settings -> API & Integrations -> Social Login —
     lets an admin hide a provider without losing its saved credentials, e.g. while that
     provider's own developer console is mid-setup) and has credentials set, same guard
     SocialAuthController itself enforces, so there's never a button that 404s when clicked.
     Nothing renders at all until at least one provider is on and configured, so an
     untouched install looks exactly like before. --}}
@php
    $socialProviders = collect([
        'google' => ['label' => 'Google', 'configured' => filled(config('services.google.client_id'))],
        'facebook' => ['label' => 'Facebook', 'configured' => filled(config('services.facebook.client_id'))],
        'linkedin' => ['label' => 'LinkedIn', 'configured' => filled(config('services.linkedin.client_id'))],
    ])->filter(fn ($p, $provider) => $p['configured'] && setting("{$provider}_login_enabled", '1') == '1');
@endphp
@if($socialProviders->isNotEmpty())
@php
    // Literal class strings, not string-built ones — Tailwind's build-time scanner only
    // picks up classes it can actually see written out in a source file, so a class
    // assembled at runtime via concatenation (e.g. 'sm:grid-cols-'.$n) would silently
    // compile to nothing and the grid would just stay single-column for everyone.
    $gridClass = match ($socialProviders->count()) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-3',
        default => '',
    };
@endphp
<div class="mb-5">
    <div class="grid grid-cols-1 {{ $gridClass }} gap-2.5">
        @foreach($socialProviders as $provider => $meta)
        <a href="{{ route('social.redirect', $provider) }}"
           class="flex items-center justify-center gap-2.5 w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-semibold py-2.5 px-4 rounded-lg transition shadow-sm">
            @if($provider === 'google')
                <svg class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0124 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
            @elseif($provider === 'facebook')
                <svg class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            @else
                <svg class="w-[18px] h-[18px] flex-shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#0A66C2" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            @endif
            <span>{{ $meta['label'] }}</span>
        </a>
        @endforeach
    </div>

    <div class="flex items-center gap-3 mt-5">
        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">or</span>
        <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
    </div>
</div>
@endif
