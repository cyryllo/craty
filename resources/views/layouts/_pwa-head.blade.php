{{-- Instalowalność jako PWA — bez trybu offline, patrz TODO.md "PWA". Współdzielone
     między layouts/app i layouts/guest, żeby manifest/service worker były
     zarejestrowane niezależnie od tego, czy ktoś jest już zalogowany. --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#4f46e5">
<link rel="apple-touch-icon" href="/pwa-icons/icon-192.png">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
