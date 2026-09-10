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

## Moduł „Aktualizacje” (specyfikacja — do budowy na sygnał „zbuduj aktualizacje”)

Model ustalony z użytkownikiem: **upload paczki ZIP**, nie samo-pobieranie z
repo ani sam podgląd `composer outdated`. My (dewelopersko, w tym repo)
budujemy i wersjonujemy paczkę aktualizacji; administrator danej instalacji
wgrywa ją przez panel, appka sama się aktualizuje na miejscu. Zakłada to
instalacje bez dostępu SSH/composer na hostingu — paczka musi więc być
samowystarczalna (patrz niżej).

**Po stronie deweloperskiej (ten build tworzy skrypt/komendę pakującą):**

- Wersjonowanie: plik `VERSION` (albo pole w `config/app.php`) z bieżącą
  wersją appki, do porównania przy walidacji paczki.
- Komenda (np. `php artisan release:build`) pakująca do `.zip`: cały kod
  appki **wraz z `vendor/` i skompilowanymi assetami** (`public/build`) —
  żeby na docelowym hostingu nie trzeba było uruchamiać composera ani npm —
  z wykluczeniem `.env`, `.git`, `node_modules`, `tests`, plików
  deweloperskich Dockera, i danych użytkownika (`storage/app/public`,
  `storage/logs`). W paczce manifest (np. `update-manifest.json`) z:
  wersją docelową, minimalną wymaganą wersją bieżącą, listą migracji do
  uruchomienia, checksumą.

**Po stronie administratora (panel w appce, Ustawienia → Aktualizacje):**

1. Pokazuje bieżącą wersję i formularz uploadu pliku `.zip`.
2. Walidacja paczki przed dotknięciem czegokolwiek: odczyt manifestu,
   sprawdzenie zgodności wersji, checksuma — odrzucić wcześnie i czytelnie,
   jeśli coś się nie zgadza.
3. **Automatyczny backup przed aktualizacją** (uruchamia moduł Backup
   opisany niżej) — twardy wymóg, nie opcja, żeby aktualizacja miała od
   czego się cofnąć.
4. Rozpakowanie do katalogu tymczasowego, dopiero potem podmiana plików
   „na żywo” — z jawną listą wykluczeń, które NIGDY nie mogą zostać
   nadpisane: `.env`, `storage/app/public/*` (zdjęcia, załączniki, logo),
   `storage/logs`, ewentualnie `public/storage` (symlink).
5. `php artisan migrate --force` dla nowych migracji z paczki.
6. Zapis nowej wersji, `php artisan config:clear`/`view:clear`, komunikat
   sukcesu z listą co się zmieniło (changelog z manifestu).
7. **Rollback** — przycisk „cofnij ostatnią aktualizację” przywracający z
   backupu wziętego w kroku 3, na wypadek gdy coś nie zadziała (zła wersja
   PHP na hoście, przerwana migracja, uszkodzona paczka).

**Bezpieczeństwo — pilnować przy budowie:**
- Rozpakowywanie ZIP-a musi być odporne na "zip slip" (wpisy w archiwum z
  `../` wychodzące poza katalog docelowy) — nie ufać ścieżkom z archiwum bez
  sanityzacji.
- Upload i zastosowanie paczki to jedna z najbardziej uprzywilejowanych akcji
  w całej appce (nadpisuje kod PHP, który się potem wykonuje) — musi być
  dostępne tylko dla `role:admin`, i warto rozważyć dodatkowe potwierdzenie
  (np. ponowne podanie hasła) przed zastosowaniem.

## Moduł „Backup” (specyfikacja — do budowy na sygnał „zbuduj backup”)

Ustalone z użytkownikiem: kopie **lokalnie na dysk, do pobrania z panelu**
(nie na Nextclouda/S3 na razie — można dodać później jako kolejne miejsce
docelowe, patrz `spatie/laravel-backup` niżej, które to obsługuje "z pudełka"
gdy będzie taka potrzeba); uruchamianie **ręczne + opcjonalny cron**.

- **Warto rozważyć pakiet `spatie/laravel-backup`** zamiast pisania własnego
  dumpu bazy/zipowania plików od zera — obsługuje dump DB (MySQL/MariaDB bez
  potrzeby binarki `mysqldump` w PATH, jeśli skonfigurowany odpowiednio),
  pakowanie wskazanych katalogów do zip, przechowywanie na wielu dyskach
  (w tym lokalnym), politykę retencji (ile kopii trzymać) i integrację z
  Laravel Schedulerem — dokładnie pasuje do tego, co tu potrzebne, podobnie
  jak `laravel-lang/lang` przy tłumaczeniach.
- **Co wchodzi w kopię:** dump bazy danych (pełny) + `storage/app/public`
  (zdjęcia przedmiotów, załączniki, logo — czyli wszystko, co użytkownik
  faktycznie wgrał i czego nie da się odtworzyć z kodu). `.env` **opcjonalnie**,
  wyraźnie zaznaczone w UI, bo zawiera sekrety (hasło do bazy, `APP_KEY`) —
  domyślnie niewłączone.
- **Panel (Ustawienia → Kopie zapasowe):** lista istniejących kopii (data,
  rozmiar) z linkiem do pobrania i przyciskiem usunięcia, przycisk „Utwórz
  kopię teraz”, oraz krótka instrukcja jak dopiąć `php artisan schedule:run`
  do crona serwera dla automatycznych kopii (appka sama nie może sobie
  założyć crona — to zawsze krok po stronie hostingu, trzeba to jasno
  napisać w UI, żeby nie wyglądało na zepsute, gdy ktoś nie skonfiguruje crona).
- **Retencja:** prosta reguła (np. „trzymaj ostatnie N kopii”) w ustawieniach
  modułu, żeby lokalny dysk się nie zapchał przy automatycznych kopiach.
- Ten moduł to też naturalny **krok 3 modułu Aktualizacje** powyżej (backup
  przed update) — budować tak, żeby dało się go wywołać programistycznie
  z innego miejsca w appce, nie tylko z przycisku w UI.

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
