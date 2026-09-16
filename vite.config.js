import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // scan.js osobno od app.js (nie w każdym requeście) — ładuje
            // @zxing/browser, spory kawałek JS potrzebny tylko na stronie skanera.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scan.js'],
            refresh: true,
            // Appka nie ma osobnego katalogu public/ (patrz CLAUDE.md "Project
            // layout") — korzeń repo JEST document rootem, więc Vite ma pisać
            // build/ wprost tam, zgodnie z public_path()==base_path() ustawionym
            // w bootstrap/app.php.
            publicDirectory: '.',
        }),
    ],
});
