import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
            colors: {
                bonusku: {
                    navy: '#0F172A',
                    'navy-soft': '#1E293B',
                    gold: '#F59E0B',
                    'gold-soft': '#FEF3C7',
                    indigo: '#4F46E5',
                    emerald: '#10B981',
                    slate: '#64748B',
                },
            },
            boxShadow: {
                card: '0 4px 6px -1px rgb(15 23 42 / 0.06), 0 2px 4px -2px rgb(15 23 42 / 0.04)',
                'card-hover': '0 10px 25px -5px rgb(15 23 42 / 0.1), 0 8px 10px -6px rgb(15 23 42 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
