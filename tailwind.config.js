import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Item upgrade overlay - filled squares
        'bg-amber-600', 'shadow-[0_0_3px_rgba(180,83,9,0.8)]',
        'bg-sky-400',   'shadow-[0_0_3px_rgba(56,189,248,0.8)]',
        'bg-yellow-300','shadow-[0_0_4px_rgba(253,224,71,0.9)]',
        'bg-stone-800/90', 'border', 'border-stone-600/60',
        // Badge colors
        'bg-amber-900/90', 'text-amber-300', 'border-amber-600/50',
        'bg-sky-900/90',   'text-sky-300',   'border-sky-500/50',
        'bg-yellow-900/90','text-yellow-200', 'border-yellow-400/60',
        'shadow-[0_0_6px_rgba(253,224,71,0.4)]',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                medieval: ['Cinzel', 'serif'],
            },
            backgroundImage: {
                'parchment': "url('data:image/svg+xml,%3Csvg width=\"100\" height=\"100\" viewBox=\"0 0 100 100\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cfilter id=\"noiseFilter\"%3E%3CfeTurbulence type=\"fractalNoise\" baseFrequency=\"0.9\" numOctaves=\"4\" stitchTiles=\"stitch\"/%3E%3C/filter%3E%3Crect width=\"100\" height=\"100\" filter=\"url(%23noiseFilter)\" opacity=\"0.3\"/%3E%3C/svg%3E')",
            },
        },
    },

    plugins: [forms],
};