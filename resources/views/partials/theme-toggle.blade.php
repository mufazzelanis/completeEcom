{{-- Dark/light mode switch — a real sliding toggle (not just an icon button) so it reads as
     clickable at a glance. Shared by the storefront header, account header, admin top bar and
     the guest/auth desktop bar; all read the same $store.theme (see resources/js/app.js). The
     icon shown is the mode you'd switch TO, not the current one — kept from the original
     design, just restyled. --}}
<button @click="$store.theme.toggle()" type="button" role="switch" :aria-checked="$store.theme.dark"
    :aria-label="$store.theme.dark ? 'Switch to light mode' : 'Switch to dark mode'"
    class="relative inline-flex flex-shrink-0 items-center w-12 h-6 rounded-full p-0.5 transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 active:scale-95 tap-spring"
    :class="$store.theme.dark ? 'bg-gradient-to-r from-indigo-900 to-slate-800' : 'bg-gradient-to-r from-sky-300 to-amber-200'">
    <span class="pointer-events-none inline-flex items-center justify-center w-5 h-5 rounded-full bg-white shadow-md transform transition-transform duration-300"
        :class="$store.theme.dark ? 'translate-x-6' : 'translate-x-0'">
        <svg x-show="!$store.theme.dark" class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg x-show="$store.theme.dark" x-cloak class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    </span>
</button>
