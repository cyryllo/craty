import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // scan.js osobno od app.js (nie w każdym requeście) — ładuje
            // @zxing/browser, spory kawałek JS potrzebny tylko na stronie skanera.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scan.js'],
            refresh: true,
        }),
    ],
});
