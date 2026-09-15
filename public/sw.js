// Minimalny service worker — celowo bez żadnego cache'owania (appka świadomie
// nie ma trybu offline, patrz TODO.md "PWA"). Sam fakt zarejestrowanego
// service workera jest jednak wymogiem checklisty instalowalności w
// Chrome/Android — bez tego przeglądarka nie zaproponuje "Zainstaluj
// aplikację", nawet gdy worker nic nie robi.
self.addEventListener('fetch', () => {
    // Pass-through: nic nie przechwytujemy, requesty idą normalnie do sieci.
});
