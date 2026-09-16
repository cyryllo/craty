import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // "class" zamiast domyślnego "media" — motyw ma być ręcznie przełączalny
    // (przycisk w nawigacji, patrz layouts/_theme-toggle.blade.php +
    // layouts/_theme-head.blade.php), nie tylko podążać za ustawieniem
    // systemowym przeglądarki.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
