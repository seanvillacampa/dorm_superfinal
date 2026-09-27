import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Use relative paths so built assets work on any domain (localhost, ngrok, production)
    base: '',
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
