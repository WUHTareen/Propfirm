import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Sora"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    300: '#5EEAD4',
                    400: '#2DD4C0',
                    500: '#14B8A6',
                    600: '#0D9488',
                    700: '#0F766E',
                },
                ink: {
                    950: '#080C12',
                    900: '#0B1017',
                    800: '#111A24',
                    700: '#16212E',
                    600: '#1E2A38',
                },
            },
        },
    },
    plugins: [],
};
