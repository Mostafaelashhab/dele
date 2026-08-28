import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'app/Livewire/**',
                'routes/**',
            ],
            // Fonts are downloaded at build time and served from our own
            // origin: no third-party request from a customer's browser, and
            // no dependency on an external CDN on shared hosting.
            fonts: [
                bunny('IBM Plex Sans Arabic', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
