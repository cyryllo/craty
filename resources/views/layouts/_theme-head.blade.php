{{-- Tryb ciemny (TODO.md "Wygoda dnia codziennego") — Tailwind skonfigurowany
     z darkMode: 'class' (tailwind.config.js), nie samym media-query, żeby
     dało się to przełączyć ręcznie niezależnie od ustawień systemu. Świadomie
     TYLKO localStorage per-przeglądarka, bez zapisu w bazie/na koncie
     (w przeciwieństwie do `User::locale`) — to jednorazowy toggle wygody, nie
     ustawienie, które ma sens synchronizować między urządzeniami.

     Ten skrypt musi wykonać się SYNCHRONICZNIE w <head>, przed pierwszym
     malowaniem strony — inaczej byłby widoczny "flash" jasnego motywu przed
     przełączeniem na ciemny (stąd zwykły <script> tutaj, nie coś czekającego
     na DOMContentLoaded czy na Alpine, które ładuje się dopiero z app.js). --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('theme');
            var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {
            // Prywatne okno / zablokowany localStorage — zostajemy przy jasnym motywie, nie wywalamy strony.
        }
    })();

    window.toggleTheme = function () {
        var dark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        } catch (e) {
            // Patrz wyżej — brak zapisu oznacza po prostu brak zapamiętania wyboru na tej wizycie.
        }
    };
</script>
