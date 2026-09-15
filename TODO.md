# TODO / mapa drogowa

Co jeszcze zostało z pierwotnej [koncepcji](https://claude.ai/code/artifact/1c7498de-ff94-4f0a-a338-0bee7e681bb8),
plus drobne rzeczy zauważone po drodze. Nic z tego nie jest w toku — to lista
do wybierania, nie backlog sprintu. Pełne treści/specyfikacje są w sekcjach
niżej — ta lista tylko ustala kolejność i tłumaczy dlaczego.

## Kolejność prac (od czego zacząć, żeby nie robić niczego dwa razy)

Ułożone wg zależności między zadaniami (co blokuje co) i tego, co już dziś
daje wartość vs. co ma sens dopiero po czymś innym. Każda faza zakłada, że
poprzednia jest zrobiona — w obrębie jednej fazy kolejność jest dowolna.

**Faza 0 — tanie poprawki przy okazji (nie blokują niczego, zrobić najpierw
bo są małe)**
1. ~~`StorageLocationController::update()` — złapać wyjątek unikalności
   regał/półka/pojemnik zamiast brzydkiego 500.~~ **Zrobione** (walidacja
   z wyprzedzeniem zamiast łapania wyjątku z bazy, 3 testy).
2. ~~Usuwanie pojedynczego zdjęcia/załącznika z karty przedmiotu.~~
   **Zrobione** (`ItemController::destroyPhoto()`/`destroyAttachment()`,
   4 testy — w tym że nie da się usunąć cudzego zdjęcia po ID).
3. `.env.testing` z zaszytym `APP_KEY` — wygenerować w pipeline, jeśli/gdy
   pojawi się wspólne CI. Nie zrobione świadomie — nie ma dziś żadnego CI,
   więc nie ma czego generować; zostawione jako przypomnienie na później.
4. Testy Blade (`assertSee`) dla `items/index`/`sale-listings/*` — dorzucać
   przy okazji, gdy i tak dotyka się tych widoków, nie jako osobne zadanie.

**Faza 1 — fundamenty pod kolejne funkcje (małe/średnie, żadne nie wymaga
jeszcze decyzji biznesowej)**
5. ~~**Ustawienia poczty / SMTP** (pełna specyfikacja niżej) — samodzielne,
   wzorowane na już istniejącym `AppSetting`, i odblokowuje punkt 11
   (powiadomienia e-mail) oraz każdą przyszłą wiadomość wysyłaną z appki.~~
   **Zrobione** (`MailSettingController`, `MailSettingsApplier`, wysyłka
   testowej wiadomości, 5 testów).
6. ~~**Moduł Backup** (pełna specyfikacja niżej) — ma wartość sam w sobie
   (bezpieczeństwo danych) już dziś, a przy okazji jest twardym wymogiem
   kroku 3 modułu Aktualizacje w fazie 2, więc musi powstać wcześniej.~~
   **Zrobione** (`spatie/laravel-backup`, `BackupService`, harmonogram w
   `routes/console.php`, 8 testów).

**Faza 1.5 — punkt pośredni, samodzielny (nie blokuje ani nie jest
blokowany przez Instalator/Aktualizacje z fazy 2)**
7. ~~**Publiczna witryna „Market”** (pełna specyfikacja niżej) — korzysta
   wyłącznie z tego, co już jest (`SaleListing`), zero nowych zależności;
   jedyna dziś publiczna (bez logowania) część appki, więc domyślnie
   wyłączona i włączana świadomie przez admina.~~ **Zrobione**
   (`MarketplaceController`, `/flea-market`, 5 testów).

**Faza 2 — wyjście poza obecny dev-loop (dopiero gdy appka ma trafić na
realny hosting, nie tylko zostać w Dockerze na tej maszynie)**
8. ~~**Instalator aplikacji** (pełna specyfikacja niżej) — pierwszy krok do
   prawdziwego wdrożenia; bez tego nie ma na czym testować punktu 9.~~
   **Zrobione** (`InstallController`, kreator jednostronicowy pod
   `/install`, 11 testów).
9. ~~**Moduł Aktualizacje** (pełna specyfikacja niżej) — zależny wprost od
   Backupu (krok 3) i sensowny dopiero, gdy istnieje już jakaś instalacja do
   aktualizowania (czyli po punkcie 8).~~ **Zrobione** (`UpdateService`,
   `release:build`, 18 testów) — backup bazy jako krok 3 jeszcze nie
   podpięty automatycznie, patrz "Drobne rzeczy zauważone przy budowie".

**Faza 3 — wartość dla codziennego użytku (z mapy drogowej + pomysły
niezależne od decyzji biznesowych)**
10. **Raporty** — korzysta z tego, co już jest w bazie, zero zmian modelu.
11. **Powiadomienia e-mail** — teraz odblokowane przez SMTP z fazy 1.
12. **Import masowy** istniejącego spisu z arkusza — najbardziej przydatne
    właśnie przy pierwszym realnym wdrożeniu u kogoś z istniejącym majątkiem
    (czyli naturalnie pasuje zaraz po fazie 2).
13. **PWA** (pełna specyfikacja niżej — instalowalność + prawdziwy skan QR
    kamerą; **bez trybu offline**, świadomie odłożonego) — spory skok
    wygody na telefonie, niezależny od reszty.
14. **Wygoda dnia codziennego** (filtry, masowe skanowanie, autouzupełnianie
    po EAN, dark mode) — drobne, można wpleść w dowolnym momencie później,
    niezależnie od kolejności innych faz.

**Faza 4 — rozszerzenia sensowne dopiero po realnym użytkowaniu albo
większe zmiany modelu danych**
15. **Inwentaryzacja okresowa** — technicznie tanie (fundament QR+lokalizacje
    już jest), ale sensowne dopiero jak jest co inwentaryzować, czyli po
    jakimś czasie realnego użycia.
16. **Serwis i konserwacja sprzętu** (przeglądy, dziennik serwisowy,
    gwarancje) — naturalnie korzysta z powiadomień e-mail z punktu 11.
17. **Rezerwacje sprzętu** — ma sens dopiero przy wielu osobach
    współdzielących warsztat naraz.
18. **Log aktywności + raport PDF wartości majątku**.
19. **Materiały eksploatacyjne** (tryb ilościowy) — duża zmiana modelu
    danych, robić świadomie i osobno, nie przy okazji czegoś innego.
20. **Eksport OLX krok C** — dopiero jeśli sprzedaż stanie się regularna;
    to zależy od realnego użycia, nie od nas, więc nie przyspieszać na siłę.
21. **Integracja z Nextcloud** — opcjonalna, niezależna od reszty listy.

**Faza 5 — duże decyzje, warunkowe**
22. **Multi-tenancy + publiczne API** — tylko jeśli appka ma faktycznie
    trafić do innych pracowni, nie tylko własnej (decyzja produktowa, nie
    techniczna — ustalić to *przed* tą fazą, nie w jej trakcie).
23. **Appka natywna Android** — dopiero jeśli PWA z punktu 13 się nie sprawdzi.

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

## Instalator aplikacji — **Zrobione** (Faza 2)

Zbudowane wg specyfikacji niżej, z kilkoma decyzjami technicznymi podjętymi
przy budowie (nieustalonymi wcześniej z użytkownikiem, bo to poziom
implementacji, nie produktu):

- **Kreator to jedna strona** (`GET /install`), nie osobna trasa na każdy z
  6 kroków — kroki 1-5 to panele Alpine.js pokazywane/ukrywane w jednym
  `<form>`, wysyłanym raz na końcu do `POST /install`. Powód: appka przed
  instalacją nie ma jeszcze bazy, a wieloetapowy kreator z osobnymi trasami
  wymagałby trzymania stanu (dane z poprzednich kroków) w sesji między
  żądaniami — kruche na tym etapie. "Testuj połączenie" w kroku 2 to
  osobne, bezstanowe żądanie AJAX (`POST /install/test-database`), nic nie
  zapisuje.
- **`APP_KEY` generowany automatycznie, zanim Laravel w ogóle wystartuje**
  (`public/index.php`, przez `App\Support\EnvFileWriter`) — bez klucza
  KAŻDE żądanie wywala się na `MissingAppKeyException` (middleware
  `EncryptCookies` wymaga klucza już przy konstrukcji), więc kreator nie
  zdążyłby nawet pokazać strony 1, gdyby czekać z generowaniem klucza do
  kroku 6, jak sugerowała pierwotna specyfikacja niżej. To rozwiązuje
  problem dla całej appki, nie tylko instalatora (dotyczy też paczek
  Aktualizacji rozpakowanych ze świeżym `.env`).
- **`.env.example` zmienione na `SESSION_DRIVER=file`, `CACHE_STORE=file`,
  `QUEUE_CONNECTION=sync`** (było: `database` dla wszystkich trzech) — appka
  nie używa dziś ani `Cache::`, ani kolejek, więc to nie miało żadnego
  powodu poza domyślnym szkieletem Laravela, a `SESSION_DRIVER=database`
  wywalał każde żądanie na świeżej, niezmigrowanej bazie (brak tabeli
  `sessions`) jeszcze przed pokazaniem kroku 1. Realny dev `.env` (już
  zmigrowany, z działającą bazą) **celowo pozostawiony bez zmian**.
- **Rozstrzygnięcie otwartego pytania o błąd w trakcie instalacji:** kreator
  nie robi żadnego rollbacku/`migrate:fresh` — przy błędzie (zły host bazy,
  migracja padnie w połowie) appka wraca na tę samą stronę z czytelnym
  komunikatem, `.env` zostaje z już zapisanymi danymi bazy. Ponowne wysłanie
  formularza jest bezpieczne w normalnym przypadku: `migrate` pomija
  migracje już wykonane, więc to naturalnie idempotentne — nie trzeba nic
  ręcznie czyścić. **Wyjątek zaobserwowany przy ręcznym teście:** jeśli sam
  proces PHP zostanie przerwany W ŚRODKU pojedynczej migracji (np. kontener
  padł dokładnie między `CREATE TABLE jobs` a wpisem do tabeli `migrations`
  — DDL w MySQL nie jest transakcyjne, więc tego etapu nie da się cofnąć),
  ponowne `migrate --force` próbuje stworzyć tę samą tabelę drugi raz i
  wybucha `Table already exists`. To rzadki, zewnętrzny scenariusz (przerwanie
  procesu, nie błąd w kodzie), ale naprawa jest wtedy ręczna — trzeba albo
  usunąć osierocone tabele, albo (najprościej na dev/pierwszej instalacji,
  gdzie i tak nie ma jeszcze żadnych realnych danych) wyczyścić całą bazę i
  zacząć kreator od nowa.
- **Strona podsumowania po instalacji** (`GET /install/done`, chroniona
  flagą na sesji — nie flash(), bo strona obsługuje jeszcze jedno kolejne
  żądanie, patrz niżej — zamiast blokady "już zainstalowane", która
  przekierowałaby stąd samą siebie na `/login`, bo admin już istnieje) —
  potwierdza sukces i pokazuje przycisk **"Usuń teraz pliki instalatora"**.
- **Usuwanie plików instalatora jednak zaimplementowane** (na sygnał
  użytkownika, po realnym teście ręcznym) — wbrew pierwotnej ostrożności
  ("appka usuwająca własny kod w trakcie działania to więcej problemów niż
  warte"), bo domknięcie tej luki miało realną wartość: sama blokada
  `EnsureNotInstalled` opiera się na zapytaniu do bazy, więc chwilowa awaria
  połączenia (`catch (\Throwable)` traktuje to jako "jeszcze
  niezainstalowane") ponownie odsłoniłaby kreator, dopóki jego pliki tam
  leżą. `App\Services\InstallerCleanupService` (root skonfigurowany przez
  `app.update_root_path` — ten sam klucz co `UpdateService`, więc testy
  operują na katalogu tymczasowym, nie na tym repo) usuwa w bezpiecznej
  kolejności: najpierw `require __DIR__.'/install.php';` z `routes/web.php`
  (żeby kolejne żądanie nigdy nie trafiło na nieistniejący plik tras),
  dopiero potem sam `routes/install.php`, `InstallController.php` i
  `resources/views/install/`. Bezpieczne mimo że kasuje plik definiujący
  klasę, z której akurat wykonuje się bieżące żądanie — PHP trzyma już
  wczytaną definicję w pamięci do końca tego żądania niezależnie od usunięcia
  pliku z dysku.
- **Automatyczne przekierowanie na `/install`, gdy appka nie jest
  zainstalowana** (na sygnał użytkownika, po realnym teście ręcznym) — nie
  tylko `EnsureNotInstalled` blokujące ponowne wejście na kreator PO
  instalacji, ale też odwrotność: `RedirectToInstallerIfNotInstalled`,
  globalny middleware w grupie `web`, wysyła KAŻDE inne żądanie (login,
  dashboard, cokolwiek) na `/install`, dopóki w bazie nie ma żadnego
  użytkownika — zamiast pokazywać ekrany, które i tak by nie zadziałały bez
  zainstalowanej bazy. Obie blokady dzielą jedną definicję "czy appka jest
  zainstalowana" (`App\Support\InstallationStatus`). Wymagało jawnego
  ustawienia priorytetu middleware (`prependToPriorityList`, zakotwiczone o
  interfejs `AuthenticatesRequests`, nie konkretną klasę `Authenticate` —
  to on jest w domyślnej liście priorytetów Laravela) — bez tego `auth`
  wykonywało się pierwsze na trasach typu `/dashboard` i gość trafiał na
  `/login` zamiast na `/install`.

**Techniczna konsekwencja dla całego pakietu testów:** skoro appka
"niezainstalowana" przekierowuje teraz wszystko, prawie każdy istniejący
test niejawnie zakładał, że appka JEST zainstalowana (nigdy nie musiał tego
zakładać jawnie). Zamiast dopisywać `User::factory()->create()` do
dziesiątek testów, `Tests\TestCase::setUp()` zakłada teraz jednego
"milczącego" admina zaraz po migracji z `RefreshDatabase`, chyba że test
jawnie ustawi `$withoutDefaultInstalledUser = true` (robią to tylko testy,
które celowo sprawdzają stan przed instalacją).

Testy: `InstallerTest`, `EnvFileWriterTest`, `InstallerCleanupServiceTest`,
`RedirectToInstallerIfNotInstalledTest` — w tym jeden pełny przebieg
end-to-end na osobnej, jednorazowej bazie scratch na tym samym kontenerze
MariaDB co dev (tworzonej i kasowanej w teście) — nie na danych
deweloperskich.

<details>
<summary>Oryginalna specyfikacja (dla kontekstu)</summary>

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
   zaseedowany `admin@craty.test` — patrz `UserController`/`User::isProtected()`),
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

**Rozstrzygnięte przy budowie** — patrz notatka nad `<details>` wyżej.

</details>

## Moduł „Aktualizacje” — **Zrobione** (Faza 2)

Zbudowane wg specyfikacji niżej, z decyzjami dopytanymi przed budową i
kilkoma technicznymi doprecyzowanymi przy niej:

- **Suma kontrolna: admin wkleja SHA-256 z release notes** (opcjonalne pole
  przy uploadzie) — realnie chroni przed uszkodzonym/podmienionym plikiem,
  bo pochodzi spoza samej paczki. Sama paczka nie niesie sumy samej siebie
  (byłoby to bez znaczenia — uszkodzona/podmieniona paczka miałaby po
  prostu inną, "zgodną z sobą" sumę).
- **Rollback przywraca tylko kod, nie bazę** — `UpdateService::apply()`
  robi własną migawkę kodu (zip całej appki, ten sam `UpdatePackageBuilder`
  co `release:build`, więc też zawiera `vendor/`/skompilowane assety) tuż
  przed nadpisaniem plików. Rollback rozpakowuje tę migawkę z powrotem.
  Automatyczne cofanie migracji świadomie pominięte (niebezpieczne dla
  dowolnych przyszłych migracji) — panel przypomina, żeby w razie potrzeby
  ręcznie przywrócić bazę z automatycznego backupu wziętego tuż przed
  aktualizacją (Moduł Backup — *uwaga: `UpdateService::apply()` dziś **nie**
  wywołuje jeszcze `BackupService::run()` automatycznie, tylko robi migawkę
  kodu; podpięcie prawdziwego backupu bazy jako krok 3 to następny mały
  krok, patrz "Drobne rzeczy zauważone przy budowie"*).
- **Dodatkowe potwierdzenie hasłem** przed upload/rollback — nie własny
  mechanizm, tylko istniejący w appce Breeze'owy `password.confirm`
  middleware (ten sam co przy standardowej zmianie hasła), zaaplikowany na
  tych dwóch trasach.
- **Manifest (`update-manifest.json`)** zawiera `version`, opcjonalne
  `min_version`, `changelog` (tablica linii) i informacyjną listę wszystkich
  plików migracji w repo (nie diff od ostatniego wydania — `migrate --force`
  i tak samo wykrywa, co jeszcze nie zostało uruchomione, więc lista w
  manifeście służy tylko do pokazania adminowi, nie steruje niczym).
- **`release:build`** (deweloperska komenda w tym repo) i migawka kodu do
  rollbacku dzielą tę samą listę wykluczeń (`UpdatePaths::PACKAGE_EXCLUDES`)
  i tę samą klasę pakującą (`UpdatePackageBuilder`) — obie mają reprezentować
  "całą działającą appkę" z tym samym wyjątkiem plików deweloperskich/danych
  użytkownika, więc nie ma sensu ich rozdzielać.
- **Zip slip** i inne bezpieczeństwo z pierwotnej specyfikacji zaimplementowane
  dokładnie jak opisano: ręczne rozpakowywanie z odrzucaniem wpisów z `../`,
  jawna lista chronionych ścieżek (`UpdatePaths::PROTECTED_PATHS`) nigdy
  nienadpisywanych przy podmianie plików.

18 nowych testów (`UpdateServiceTest`, `UpdateControllerTest`,
`UpdatePackageBuilderTest`, `BuildReleasePackageTest`, `AppVersionTest`) —
wszystkie operujące na katalogach tymczasowych, nigdy na tym repo (poza
jednym testem komendy `release:build`, który bezpiecznie tylko CZYTA
prawdziwe drzewo źródłowe do zbudowania paczki testowej, zapisywanej do
katalogu tymczasowego).

<details>
<summary>Oryginalna specyfikacja (dla kontekstu)</summary>

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

</details>

## Moduł „Backup” — **Zrobione** (Faza 1)

Zbudowane wg specyfikacji niżej, z dwoma odstępstwami odnotowanymi na
przyszłość:
- **Retencja jest dniowa, nie ilościowa** — `spatie/laravel-backup`'owa
  `DefaultStrategy` domyślnie liczy w dniach ("trzymaj pełne kopie z
  ostatnich N dni"), nie w sztukach ("trzymaj ostatnie N kopii"), więc UI
  (`Ustawienia → Kopie zapasowe`) opisuje to uczciwie jako dni, żeby nie
  wprowadzać w błąd.
- **Obraz Dockera wymagał doinstalowania `mariadb-client`** (dostarcza
  `mysqldump`), bo `spatie/laravel-backup` woła tę binarkę do zrzutu bazy —
  bez tego backup 500-ował z `DumpFailed`. Patrz `Dockerfile`.

Harmonogram (`routes/console.php`) woła `BackupService::run()`/`cleanup()`
przez `Schedule::call()`, a nie `backup:run`/`backup:clean` bezpośrednio —
dzięki temu zaplanowane uruchomienia respektują ustawienia admina (retencja,
dołączanie `.env`), tak samo jak ręczne uruchomienie z panelu.

<details>
<summary>Oryginalna specyfikacja (dla kontekstu)</summary>

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

</details>

## Ustawienia poczty / SMTP — **Zrobione** (Faza 1)

Zbudowane wg specyfikacji niżej. Nadpisywanie configu robi
`App\Services\MailSettingsApplier`, wołane bezpośrednio przed wysyłką (nie
middleware'em na każde żądanie) — dokładnie tak, jak specyfikacja to
przewidywała jako bezpieczniejszą opcję. Jedna techniczna rozbieżność ze
specyfikacją: nowoczesny Laravel/Symfony Mailer nie ma już klucza
`mail.mailers.smtp.encryption` — szyfrowanie ustala się przez `scheme`
(`smtps` dla SSL/portu 465, `smtp` dla TLS/STARTTLS negocjowanego
automatycznie), patrz komentarz w `MailSettingsApplier::applyFromArray()`.

<details>
<summary>Oryginalna specyfikacja (dla kontekstu)</summary>

Prerekwizyt pod przyszłe powiadomienia e-mail (patrz „Pomysły do rozważenia”
niżej) — bez skonfigurowanej poczty nie ma czego wysyłać. Ten sam wzorzec co
`AppSetting` (logo/nazwa/język): admin konfiguruje z panelu, appka trzyma to
w bazie, nie tylko w `.env`, żeby nie wymagać dostępu do plików serwera przy
zwykłej zmianie hasła do skrzynki.

- **Nowa strona w Ustawienia** (tylko admin): host SMTP, port, szyfrowanie
  (tls/ssl/brak), login, hasło, adres i nazwa nadawcy („od kogo”).
- **Hasło szyfrowane w bazie** (Eloquent `encrypted` cast) — to jedyne
  miejsce w appce, gdzie w bazie ląduje sekret tego typu, więc nie trzymać
  go jawnym tekstem tak jak resztę `AppSetting`.
- **Przycisk „wyślij testową wiadomość”** przed zapisaniem na stałe — wysyłka
  próbna na adres podany w formularzu (np. e-mail zalogowanego admina),
  z czytelnym błędem połączenia zamiast suchego wyjątku, jeśli dane są złe.
- **Strona techniczna:** appka domyślnie czyta konfigurację poczty z `.env`
  przy starcie (`config('mail...')`), więc żeby ustawienia z bazy faktycznie
  zadziałały, trzeba je nadpisywać w locie — albo tym samym middleware'owym
  wzorcem co `SetLocale` (nadpisanie `config(['mail.mailers.smtp' => ...])`
  na starcie żądania), albo bezpieczniej: nadpisywać dopiero bezpośrednio
  przed wysyłką maila (jedno miejsce w kodzie, nie każde żądanie HTTP).
  Gdy w bazie nic nie ustawiono, appka ma spadać z powrotem na `.env` — nie
  wymuszać konfiguracji przez UI, żeby dev/Docker nie przestał wysyłać maili
  (dziś `MAIL_MAILER=log`, patrz `.env`).
- Sam ekran ustawień nie wysyła jeszcze żadnych powiadomień — to osobna
  funkcjonalność z listy pomysłów niżej, budowana później na tym fundamencie.

</details>

## Publiczna witryna „Flea market” (pchli targ) — **Zrobione** (Faza 1.5)

Zbudowane wg specyfikacji niżej, z jedną zmianą już po specyfikowaniu: adres
i angielska nazwa źródłowa to **„Flea market”** (`/flea-market`), nie
dosłowne tłumaczenie polskiego „pchli targ” na jedno słowo — trafniej oddaje
sens (używane przedmioty, okazjonalna sprzedaż, żadnego sklepu) niż ogólne
„Market”, które było rozważane pośrodku i odrzucone. Polskie tłumaczenie w
`lang/pl.json` to „Pchli targ”.

Dodatkowo, obok e-maila kontaktowego: opcjonalne pole **„Telefon kontaktowy”**
(`AppSetting::public_contact_phone`), pokazywane na stronie jako link `tel:`
tuż obok linku `mailto:` na każdej ofercie.

5 nowych testów (`MarketplaceTest`), włącznie z tym że wyłączona strona
zwraca 404 i że sprzedane oferty nie są widoczne.

<details>
<summary>Oryginalna specyfikacja (dla kontekstu)</summary>

Jedyna dziś planowana **publiczna** (bez logowania) część appki — wszystko
inne wymaga konta. Pokazuje na zewnątrz to, co i tak już trafia na OLX przez
eksport CSV (`SaleListing`), tylko jako własna podstrona zamiast/obok
ogłoszenia u pośrednika. Zero zmian w modelu `SaleListing` — `title`,
`description`, `price` już tam są.

- **Adres:** `/pchli-targ`, osobna grupa tras **poza** middleware `auth`
  (ale nadal przez `SetLocale`, żeby strona szła w domyślnym języku appki —
  gość nie ma konta, więc nie ma z czego czytać osobistej preferencji
  języka; używa `AppSetting::current()->locale`).
- **Włącznik w Ustawienia → Ustawienia aplikacji** (nowe pole na
  `AppSetting`, np. `public_marketplace_enabled`, domyślnie `false`) — gdy
  wyłączony, trasa zwraca 404, a nie pustą stronę, żeby nie zdradzać nawet
  istnienia adresu. Obok włącznika: pole **„E-mail kontaktowy do ofert”**
  (osobne od „From address” w Ustawienia → Poczta — administrator może
  chcieć inną skrzynkę do korespondencji z kupującymi niż techniczny adres
  nadawcy SMTP), pokazywane/wymagane tylko gdy włącznik jest zaznaczony.
- **Dane źródłowe:** tylko `SaleListing` ze statusem `wyeksportowana`
  („Listed”) — **oferty ze statusem `sprzedana` znikają z listy całkowicie**
  (ustalone: prościej niż trzymać na stronie nieaktualne/niedostępne pozycje,
  i nie trzeba pilnować, żeby ktoś nie napisał o coś, co już sprzedane).
- **Jedna strona, bez podstron per-oferta** (ustalone: mniej do zbudowania,
  wszystko widoczne od razu na kafelku/w wierszu — zdjęcie, tytuł, cena,
  opis; brak osobnego URL do wysłania komuś pojedynczej oferty na razie).
- **Przełącznik lista/kafle**, tym samym wzorcem co `/items`
  (`ItemController::index()` + `session('items_view')`) — osobny klucz sesji
  (np. `marketplace_view`), żeby wybór na stronie publicznej nie nadpisywał
  preferencji zalogowanego użytkownika w panelu i odwrotnie.
- **Pokazywane pola:** zdjęcie (`item->primaryPhoto`, placeholder gdy brak),
  `title`, `price`, `description` z `SaleListing` — **nie** dane wewnętrzne
  przedmiotu (`inventory_no`, lokalizacja, numer seryjny/EAN, wartość
  księgowa) — to ogłoszenie sprzedażowe, nie kartoteka magazynowa.
- **Kontakt:** link `mailto:` na skonfigurowany e-mail kontaktowy, z
  tematem wstępnie wypełnionym tytułem oferty (`?subject=...`) — żadnego
  formularza/koszyka/płatności, appka niczego nie sprzedaje sama.
- **SEO/roboty:** poza zakresem na start — appka to narzędzie do jednego
  warsztatu, nie sklep do pozycjonowania; nie dodawać `sitemap.xml`/meta
  Open Graph, dopóki ktoś tego realnie nie potrzebuje.

</details>

## PWA (specyfikacja — do budowy na sygnał „zbuduj PWA”)

Zakres świadomie zawężony po rozmowie z użytkownikiem — **bez trybu
offline w ogóle na razie** (ani samego cache'owania stron, ani tym bardziej
zapisu offline z synchronizacją) i **bez akcji po skanie** (skan tylko
przenosi do karty przedmiotu, tak jak dziś klik w link z zeskanowanego
kodu — żadnego menu "wypożycz/zwróć" na tym etapie). Dwie osobne, niezależne
części:

**1. Instalowalność (appka jako ikonka na ekranie głównym)**
- `public/manifest.json` (nazwa, `short_name`, `start_url` → `/dashboard`,
  `display: standalone`, `theme_color`/`background_color`, ikony 192×192 i
  512×512 + wariant "maskable"), podpięty w `<head>` layoutu przez
  `<link rel="manifest">`.
- Ikony na start: **stały, wbudowany zestaw** wygenerowany raz z domyślnego
  loga Craty (regał z półkami, ten sam SVG co `x-application-logo`) —
  **nie** dynamicznie z własnego loga admina wgranego w Ustawienia →
  Ustawienia aplikacji. Zrobienie tego per-instalacja (rasteryzacja
  wgranego obrazka do wymaganych rozmiarów/wariantu maskable, cache,
  invalidacja przy zmianie loga) to więcej roboty niż to na dziś warte —
  dopisane do "Pomysłów do rozważenia później" jako możliwe rozszerzenie.
- **Minimalny service worker** — sam plik `sw.js` z pustym/pass-through
  handlerem `fetch` (bez żadnego cache'owania) jest tu tylko po to, żeby
  Chrome/Android w ogóle zaproponowały "Zainstaluj aplikację" — część
  przeglądarek wymaga zarejestrowanego service workera jako kryterium
  instalowalności, nawet gdy nic nie robi. To nie jest offline-first, to
  techniczny wymóg checklisty PWA.
- **Wymaga HTTPS** (albo `localhost`) — `getUserMedia`/kamera i rejestracja
  service workera nie działają na zwykłym HTTP w produkcji; dopisać to
  wyraźnie do instrukcji wdrożenia (patrz też Instalator, jeśli już będzie
  gotowy), żeby ktoś się nie zdziwił, że "PWA nie działa" na hostingu bez
  certyfikatu.

**2. Prawdziwy skan QR/kodu kreskowego kamerą**
- Nowy przycisk/strona "Skanuj" w nawigacji (widoczny tam, gdzie dziś widać
  `/items` — czyli każda zalogowana rola) — otwiera kamerę wprost w
  przeglądarce (biblioteka JS do dekodowania obrazu wideo, dociągnięta przez
  npm/Vite, **nie** z CDN — appka nie ma dziś żadnych zależności z CDN w
  produkcyjnym buildzie i nie ma po co taki precedens zaczynać). Jedna
  biblioteka obsługuje od razu i QR, i typowe kody kreskowe 1D (EAN-13/
  UPC-A) — to ważne pod punkt 3 niżej.
- **Dwa różne rodzaje zeskanowanego kodu, dwie różne ścieżki:**
  - **Własny QR z etykiety Craty** — koduje pełny URL strony przedmiotu
    (`QrCodeGenerator`, patrz CLAUDE.md) już dziś, więc po zdekodowaniu
    skaner robi zwyczajne `window.location.href = zdekodowanyTekst`. Zero
    nowych endpointów backendowych, cała obsługa po stronie klienta.
  - **Kod kreskowy producenta (EAN/UPC) na samym przedmiocie** — to nie
    jest URL, tylko goły numer. Trafia do punktu 3.
- Czytelny błąd, gdy przeglądarka odmówi dostępu do kamery (brak
  uprawnień, brak HTTPS, brak kamery w ogóle na desktopie) — nie biała
  strona/wyjątek JS.

**3. Szybkie dodawanie, gdy zeskanowany kod nie pasuje do niczego w bazie**
- Zeskanowany numeryczny kod kreskowy (nie URL) jest sprawdzany przez nowy,
  lekki endpoint (`GET`, zwraca JSON) po `Item::ean`/`Item::serial_number`.
  Trafia → tak samo jak przy własnym QR, przekierowanie do karty przedmiotu.
  Nie trafia → appka pokazuje przycisk **„+ Dodaj jako nowy przedmiot”**
  zamiast błędu "nie znaleziono".
- Formularz pod tym przyciskiem jest **celowo uproszczony** względem
  pełnego `/items/create`: tylko zdjęcie (natywny aparat telefonu przez
  `<input type="file" capture="environment">`, jedno zdjęcie, nie cała
  galeria), nazwa i sam zeskanowany kod (ląduje w `ean`). Bez kategorii,
  lokalizacji, stanu, wartości — to ma zająć kilka sekund w warsztacie, nie
  zastępować pełnego wprowadzania danych. `InventoryNumberGenerator` już
  dziś radzi sobie z brakiem kategorii/lokalizacji (`GEN`/`BRAK` w numerze),
  więc backend tego nie wymaga.
- Tak dodany przedmiot dostaje nową flagę `Item::needs_completion` (bool,
  domyślnie `false`) — **wyróżnia się na `/items`** small ikonką (📱, przez
  `x-icon`) przy nazwie, żeby magazynier widział "to jest dodane naprędce ze
  skanera, brakuje mu kategorii/lokalizacji/reszty danych". Flaga czyści się
  automatycznie przy najbliższym zapisaniu przedmiotu przez zwykły formularz
  edycji (`ItemController::update()`) — uznajemy, że skoro ktoś przeszedł
  przez pełną edycję, to już to przejrzał; **nie** próbujemy zgadywać
  "kompletności" po tym, czy akurat kategoria/lokalizacja są wypełnione (za
  dużo przypadków brzegowych, prościej i uczciwiej trzymać to jako prosty
  fakt "ktoś to dotknął po dodaniu ze skanera").
- Uprawnienia jak przy zwykłym dodawaniu przedmiotu — `role:admin,
  magazynier`, nie `podglad` (sam skan-do-podglądu istniejącego przedmiotu
  zostaje dostępny dla każdej roli, tak jak dziś `/items/{item}`).

## Pomysły do rozważenia później (bez ustalonych decyzji, nie specyfikacja)

Luźny brainstorm, co jeszcze bywa przydatne w tego typu systemach (CMMS /
asset management, jak Snipe-IT czy EZOfficeInventory) — nic z tego nie jest
ustalone ani uzgodnione co do sposobu działania, w przeciwieństwie do
instalatora/aktualizacji/backupu wyżej. Gdy któryś kierunek stanie się
aktualny, przegadać go tak samo jak tamte, zanim zacznie się budować.

**Serwis i konserwacja sprzętu**
- Harmonogram przeglądów (np. „co 6 miesięcy”) z ostrzeżeniem na dashboardzie,
  tym samym wzorcem co dziś przeterminowane wypożyczenia.
- Dziennik serwisowy przedmiotu — historia napraw (co, kto, jaki koszt),
  bogatsza niż dzisiejszy sam status „w naprawie”.
- Termin gwarancji jako pole z datą + ostrzeżenie przed wygaśnięciem.

**Materiały eksploatacyjne**
- Dziś model zakłada „1 przedmiot = 1 sztuka”. Dla śrubek/kleju/materiałów
  przydałby się tryb ilościowy (sztuki/metry/litry) z progiem minimalnym i
  alertem „kończy się” — większa zmiana modelu danych, nie kosmetyka.

**Inwentaryzacja okresowa**
- Tryb „policz stan”: skanowanie kolejnych QR na regale, porównanie z tym,
  co powinno tam być, raport rozbieżności na koniec. Fundament (QR +
  lokalizacje) już jest, więc to relatywnie tanie do zrobienia.

**Rezerwacje sprzętu**
- Dziś wypożyczenie jest „na już”. Rezerwacja na przyszły termin ma sens,
  gdy z warsztatu korzysta więcej niż jedna osoba naraz.

**Widoczność i rozliczalność**
- Ogólny log aktywności appki (logowania, zmiany użytkowników/ustawień), nie
  tylko historia pojedynczego przedmiotu.
- Raport wartości majątku do PDF (lista + zdjęcia + wartości) — przydatny
  przy ubezpieczeniu/szkodzie.
- Powiadomienia e-mail (przeterminowane wypożyczenie, zbliżający się
  przegląd/gwarancja), nie tylko widok na dashboardzie — fundament pod to
  (ustawienia SMTP) już wyżej jako osobna, gotowa specyfikacja.

**Wygoda dnia codziennego**
- Zapisane/zaawansowane filtry, sortowanie po kliknięciu nagłówka kolumny.
- Masowe skanowanie QR pod rząd (np. wydanie całego zestawu na wyjazd naraz).
- Autouzupełnianie po EAN przy dodawaniu przedmiotu (nazwa/zdjęcie z
  zewnętrznej bazy produktów po zeskanowaniu kodu kreskowego) — częściowo
  pokrywa się z "szybkim dodawaniem" ze specyfikacji PWA (punkt 3), które
  bierze tylko zdjęcie+nazwę+kod bez zewnętrznego źródła danych; to jest
  wersja "plus" tamtego pomysłu, gdyby się okazało, że ręczne wpisywanie
  nazwy przy skanowaniu jednak przeszkadza.
- Tryb ciemny UI.
- **Ikona PWA z własnego loga admina**, zamiast stałego domyślnego zestawu
  ustalonego w specyfikacji PWA — wymaga rasteryzacji wgranego obrazka do
  wymaganych rozmiarów/wariantu maskable przy każdej zmianie loga w
  Ustawienia → Ustawienia aplikacji, więc świadomie odłożone na potem.

**Jeśli appka miałaby trafić do innych pracowni, nie tylko własnej**
- Multi-tenancy (wiele niezależnych organizacji w jednej instalacji) i
  publiczne REST API do integracji z innymi narzędziami — duże decyzje
  architektoniczne, więc przemyśleć wcześniej niż później, jeśli to realny
  kierunek (paczki aktualizacji „dla użytkowników” już na to wskazują).

## Drobne rzeczy zauważone przy budowie

- Brak `assertSee`-owych testów Blade dla widoków (`items/index`,
  `sale-listings/*`) poza tym, co pokrywają testy feature na kontrolerach —
  wystarczające jak na szkielet, ale warto rozbudować przy większych zmianach UI.
- `.env.testing` ma zaszyty na sztywno `APP_KEY` — jeśli kiedyś repo trafi do
  współdzielonego CI, rozważ wygenerowanie go w pipeline zamiast trzymania w
  repo (ryzyko niskie, to tylko klucz do efemerycznej bazy testowej).
- **Pchli targ pokazuje tylko główne zdjęcie przedmiotu**, nawet gdy jest ich
  kilka — świadomie odłożone (pytanie użytkownika, decyzja: zostawić jak
  jest na razie), bo strona celowo nie ma podstron per-oferta, gdzie dałoby
  się pokazać galerię. Gdy to wróci jako temat, rozważyć pasek miniaturek pod
  głównym zdjęciem (podmiana przez Alpine, bez nowego URL-a) zamiast pełnej
  podstrony/lightboxa.
- **Moduł Aktualizacje jeszcze nie woła `BackupService::run()` automatycznie**
  przed zastosowaniem paczki (krok 3 z oryginalnej specyfikacji) — dziś robi
  tylko własną migawkę kodu. Podpięcie prawdziwego backupu bazy jako
  kolejny krok `UpdateService::apply()` to małe, osobne zadanie (backup
  moduł już istnieje i ma dokładnie taki interfejs, `app(BackupService::
  class)->run()`, żeby dało się to łatwo dopisać).
- **Wgrywanie paczki aktualizacji może uderzyć w limity PHP**
  (`upload_max_filesize`/`post_max_size` w php.ini) — pełna paczka z
  `vendor/`/skompilowanymi assetami waży dziś ~26 MB, a domyślne limity PHP
  bywają dużo niższe (często 2-8 MB). Warto to dopisać do przyszłego kroku
  1 Instalatora ("Wymagania środowiska") albo przynajmniej pokazać czytelny
  komunikat na stronie Ustawienia → Aktualizacje, zamiast pozwolić appce
  ucinać upload w milczeniu.
