import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Colors used only via runtime interpolation (e.g. `bg-{{ $channel['color'] }}-600` in
// notification/payment-method admin screens) — Tailwind's scanner can't see a class name
// that's assembled from a PHP variable, so those shades would otherwise get purged from
// the production build. Catalogued from every `'color' => '...'` array literal in
// resources/views (notifications, payment gateways, admin dashboard stat cards).
const dynamicColors = [
    'blue', 'green', 'red', 'yellow', 'amber', 'purple', 'emerald',
    'indigo', 'teal', 'pink', 'orange', 'sky', 'gray',
];
// Only 100/500/600/700 are ever actually assembled at runtime (bg-{color}-100,
// text-{color}-500/600/700) — grepped every `(bg|text|border|ring)-{{ }}-<n>` in
// resources/views to confirm. The full 50–900 range was pure dead-weight CSS.
const dynamicShades = ['100', '500', '600', '700'];

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Model accessors like Order::getStatusBadgeAttribute() return literal Tailwind
        // classes (e.g. 'bg-yellow-100 text-yellow-800') straight from PHP, not from a view —
        // Tailwind needs to scan those files too or it purges the classes they return.
        './app/**/*.php',
    ],
    safelist: [
        {
            // border- was never actually needed either (grepped, zero dynamic border-{{ }}
            // usages) — just bg/text/ring.
            pattern: new RegExp(`^(bg|text|ring)-(${dynamicColors.join('|')})-(${dynamicShades.join('|')})$`),
            // Only `peer-checked:bg-{color}-600` is actually built dynamically anywhere
            // (the notification-channel toggle switches) — no dynamic hover:/focus: color
            // classes exist, so no need to safelist those variant combinations too.
            variants: ['peer-checked'],
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Matches the CDN build's runtime `tailwind.config` default fallback so
            // `bg-primary-*` etc. keep working if it's ever used — currently unused in
            // practice (the storefront re-themes brand colors by overriding the literal
            // `orange-*` classes directly instead, see layouts/app.blade.php).
            colors: {
                primary: {
                    50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c',
                    500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12',
                },
            },
        },
    },

    plugins: [forms],
};
