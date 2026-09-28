{{-- Color classes (bg-orange-600 etc.) deliberately left as literal Tailwind utilities, not
     inline styles — layouts/guest.blade.php re-themes every one of them via injected CSS
     when the admin sets a custom primary color (Settings → Branding), the same mechanism
     the rest of the storefront uses. The shine sweep/arrow below are color-neutral so they
     keep working under any brand color without their own override rules. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'group relative inline-flex items-center justify-center gap-2 overflow-hidden px-5 py-2.5 bg-orange-600 border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide shadow-md hover:bg-orange-700 hover:shadow-lg focus:bg-orange-700 active:bg-orange-800 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition']) }}>
    <span class="pointer-events-none absolute inset-y-0 left-0 w-1/3 -skew-x-12 bg-white/25 -translate-x-[150%] group-hover:translate-x-[350%] transition-transform duration-700 ease-out"></span>
    <span class="relative">{{ $slot }}</span>
    <svg class="relative w-4 h-4 flex-shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
</button>
