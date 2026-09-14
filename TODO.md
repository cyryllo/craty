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
8. **Instalator aplikacji** (pełna specyfikacja niżej) — pierwszy krok do
   prawdziwego wdrożenia; bez tego nie ma na czym testować punktu 9.
9. **Moduł Aktualizacje** (pełna specyfikacja niżej) — zależny wprost od
   Backupu (krok 3) i sensowny dopiero, gdy istnieje już jakaś instalacja do
   aktualizowania (czyli po punkcie 8).

**Faza 3 — wartość dla codziennego użytku (z mapy drogowej + pomysły
niezależne od decyzji biznesowych)**
10. **Raporty** — korzysta z tego, co już jest w bazie, zero zmian modelu.
11. **Powiadomienia e-mail** — teraz odblokowane przez SMTP z fazy 1.
12. **Import masowy** istniejącego spisu z arkusza — najbardziej przydatne
    właśnie przy pierwszym realnym wdrożeniu u kogoś z istniejącym majątkiem
    (czyli naturalnie pasuje zaraz po fazie 2).
13. **PWA** (skan QR kamerą, offline) — spory skok wygody na telefonie,
    niezależny od reszty.
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
  zewnętrznej bazy produktów po zeskanowaniu kodu kreskowego).
- Tryb ciemny UI.

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
