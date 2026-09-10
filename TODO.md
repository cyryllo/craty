# TODO / mapa drogowa

Co jeszcze zostało z pierwotnej [koncepcji](https://claude.ai/code/artifact/1c7498de-ff94-4f0a-a338-0bee7e681bb8),
plus drobne rzeczy zauważone po drodze. Nic z tego nie jest w toku — to lista
do wybierania, nie backlog sprintu.

## Z mapy drogowej (kolejne etapy)

- **Raporty** — wartość magazynu w czasie, zestawienia wg kategorii/lokalizacji.
- **Import masowy** istniejącego spisu z arkusza CSV/Excel.
- **PWA** — instalacja na telefonie/tablecie, prawdziwe skanowanie QR kamerą
  (dziś kod QR tylko linkuje do strony przedmiotu, otwieranej ręcznie w
  przeglądarce po zeskanowaniu aparatem).
- **Integracja z Nextcloud** (opcjonalna) — SSO logowania (OIDC), zdjęcia/
  załączniki na WebDAV zamiast lokalnego dysku.
- **Eksport OLX krok C** — jeśli sprzedaż stanie się regularna: integracja z
  narzędziem pośredniczącym (BaseLinker/Apilo) albo własny dostęp do OLX API.
  Patrz uzasadnienie w dokumencie koncepcyjnym, sekcja 07 — nie ma publicznego
  bulk-API dla zwykłych kont, więc to świadomie odłożone, nie zapomniane.
- Appka natywna (Android), jeśli PWA się nie sprawdzi.

## Drobne rzeczy zauważone przy budowie

- `StorageLocationController::update()` nie łapie w ładny sposób wyjątku przy
  próbie ustawienia kombinacji regał/półka/pojemnik, która już istnieje w tym
  magazynie (unique constraint na `storage_locations.code`) — skończy się
  brzydkim 500 zamiast komunikatu walidacji.
- Brak usuwania pojedynczego zdjęcia/załącznika z karty przedmiotu — da się
  tylko dodawać, albo usunąć cały przedmiot.
- Brak `assertSee`-owych testów Blade dla widoków (`items/index`,
  `sale-listings/*`) poza tym, co pokrywają testy feature na kontrolerach —
  wystarczające jak na szkielet, ale warto rozbudować przy większych zmianach UI.
- `.env.testing` ma zaszyty na sztywno `APP_KEY` — jeśli kiedyś repo trafi do
  współdzielonego CI, rozważ wygenerowanie go w pipeline zamiast trzymania w
  repo (ryzyko niskie, to tylko klucz do efemerycznej bazy testowej).
