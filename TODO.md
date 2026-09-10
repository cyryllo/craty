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

## Instalator aplikacji (specyfikacja — do budowy na sygnał „zbuduj instalator”)

Kreator webowy do stawiania appki na docelowym hostingu (nie zastępuje
obecnego dev-loopu z Dockerem i `DatabaseSeeder` — to osobna ścieżka dla
prawdziwego wdrożenia, np. na zwykłym hostingu PHP bez SSH). Decyzje już
podjęte z użytkownikiem, żeby nie trzeba było dopytywać przy starcie budowy:

- **Typ:** kreator webowy (wieloetapowy formularz w przeglądarce), nie
  komenda artisan.
- **Baza danych:** instalator sam zapisuje `.env` (host/port/nazwa/login/
  hasło z formularza, z „testuj połączenie” przed zapisem) — nie wymaga
  wcześniej ręcznie skonfigurowanego `.env`.
- **Blokada ponownej instalacji:** pełna, automatyczna. Jeśli w bazie istnieje
  jakikolwiek użytkownik (`User::query()->exists()` — nie osobna flaga/plik),
  każde wejście na trasy `/install*` przekierowuje na `/login`. Ten sam
  warunek musi chronić *każdy* krok kreatora, nie tylko stronę startową —
  nie powinno dać się dokończyć kroku kreatora z zakładki zapisanej w
  historii przeglądarki, jeśli instalacja w międzyczasie już się skończyła.
- **Dane demo:** opcjonalny checkbox „załaduj dane przykładowe” w ostatnim
  kroku — jeśli zaznaczony, uruchamia samą część przykładowych
  kategorii/magazynu/przedmiotów z `DatabaseSeeder` (bez fałszywych kont
  magazyniera/podglądu — to sensowne tylko na dev). Prawdopodobnie trzeba
  rozbić dzisiejszy `DatabaseSeeder` na dwie części: tworzenie kont demo
  (zostaje tylko do dev) i samodzielny `DemoDataSeeder` z
  kategoriami/magazynem/przedmiotami, który wywoła też instalator.

Kroki kreatora (roboczo):

1. **Wymagania środowiska** — wersja PHP (≥ 8.2 wg `composer.json`),
   rozszerzenia (`pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`, `exif`,
   `intl` — patrz `Dockerfile`), zapisywalność `storage/`,
   `bootstrap/cache/` i pliku `.env` (albo jego katalogu, gdy `.env` jeszcze
   nie istnieje — wtedy skopiować `.env.example` → `.env` przed edycją).
   Czerwone/zielone światła jak w instalatorze WordPressa, blokada „dalej”
   dopóki coś krytycznego nie gra.
2. **Baza danych** — host/port/nazwa/login/hasło + przycisk „testuj
   połączenie” (osobne żądanie, łapiące wyjątek połączenia, bez zapisu do
   `.env` dopóki test nie przejdzie).
3. **Nazwa aplikacji** — trafia do `AppSetting.name` (branding/logo zostają
   do zrobienia już po instalacji w Ustawienia → Ustawienia aplikacji, nie
   trzeba tego dublować w kreatorze).
4. **Konto głównego administratora** — imię, e-mail, hasło (+ potwierdzenie).
   To konto musi dostać `protected = true` (ten sam mechanizm co dzisiejszy
   zaseedowany `admin@graty.test` — patrz `UserController`/`User::isProtected()`),
   żeby od razu było chronione przed usunięciem/degradacją.
5. **Dane przykładowe** — checkbox opisany wyżej.
6. **Podsumowanie i uruchomienie** — w tym miejscu dopiero: zapis `.env`,
   `php artisan key:generate` (jeśli `APP_KEY` puste), migracje, zapis
   `AppSetting`, utworzenie admina, ewentualny `DemoDataSeeder`,
   `php artisan config:clear` (bo `.env` zmienił się już po starcie procesu),
   przekierowanie na `/login` z komunikatem powodzenia.

Do przemyślenia przy budowie: obsługa błędu w trakcie kroku 6 (np. migracja
padnie w połowie) — czy wracać do kroku 2 z komunikatem, czy wymagać ręcznego
`migrate:fresh`; to nie zostało jeszcze ustalone z użytkownikiem.

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
