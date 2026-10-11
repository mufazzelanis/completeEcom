import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import plugin from 'tailwindcss/plugin';
import colors from 'tailwindcss/colors';

// Brand theming: the orange / pink / red palettes are CSS variables, not fixed hexes —
// orange = Primary, pink = Secondary, red = Accent (Settings → Branding; a landing page's own
// brand color for its page). brand_palette_css() (app/helpers.php) writes the admin's shades
// into :root at runtime, so every utility built from these palettes — bg/text/border/ring,
// gradients, shadows, opacity variants like bg-orange-500/10, hover/focus/dark states —
// follows the brand automatically. Defaults (below, via addBase) are Tailwind's own values,
// so nothing changes for a store that never picked custom colors.
const themedPalettes = { brand: colors.orange, secondary: colors.pink, accent: colors.red };
const hexToRgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(' ');
const varPalette = (name) => Object.fromEntries(
    Object.keys(themedPalettes[name]).map((shade) => [shade, `rgb(var(--${name}-${shade}) / <alpha-value>)`]),
);
const paletteDefaults = plugin(({ addBase }) => addBase({
    ':root': Object.fromEntries(Object.entries(themedPalettes).flatMap(([name, palette]) =>
        Object.entries(palette).map(([shade, hex]) => [`--${name}-${shade}`, hexToRgb(hex)]))),
}));

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
                orange: varPalette('brand'),
                pink: varPalette('secondary'),
                red: varPalette('accent'),
                primary: {
                    50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c',
                    500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12',
                },
            },
        },
    },

    // typography: renders admin-authored WYSIWYG content (Summernote output — landing pages,
    // blog posts, CMS pages) with sane default spacing/heading sizes instead of raw
    // unstyled HTML, via the `prose` class.
    plugins: [forms, typography, paletteDefaults],
};
