# Historia zmian

Zapis tego, co powstało w projekcie i dlaczego — w kolejności chronologicznej.
Szerszy kontekst decyzji (stack, architektura, uzasadnienie eksportu OLX) jest
w opublikowanym dokumencie koncepcyjnym ["Ewidencja Graty"](https://claude.ai/code/artifact/1c7498de-ff94-4f0a-a338-0bee7e681bb8)
oraz w [README.md](README.md).

## 2026-09-10 — Szkielet aplikacji

Laravel 12 + Breeze (blade/Tailwind), całe środowisko dev w Dockerze (na tej
maszynie nie ma lokalnego PHP/Composera) — patrz `CLAUDE.md`.

- logowanie, role `admin` / `magazynier` / `podglad` (rejestracja świadomie
  wyłączona — konta zakłada admin w `/users`)
- kategorie → magazyny → lokalizacje (regał/półka/pojemnik) z generowanym
  kodem lokalizacji
- kartoteka przedmiotu: zdjęcia, załączniki, specyfikacja, wartość, stan,
  status
- automatyczny numer ewidencyjny (`InventoryNumberGenerator`, atomowy licznik
  roczny) + generowanie kodu QR (`QrCodeGenerator`) i etykiety do druku
- historia zmian przedmiotu (`ItemObserver`), wypożyczenia, pierwsza wersja
  ofert sprzedaży + eksportu CSV
- dane startowe (3 konta, kategorie, magazyn, przykładowe przedmioty)
- 27 testów PHPUnit na osobnej bazie SQLite

## 2026-09-10 — Zakładka „Sprzedaż”

Zamiast przycisku eksportu ukrytego w nagłówku listy przedmiotów: osobna
zakładka w menu z widokiem przygotowanych ofert i historią eksportów.

## 2026-09-10 — Numer seryjny i EAN

Opcjonalne pola `serial_number` i `ean` na przedmiocie (odrębne od
wewnętrznego numeru ewidencyjnego) — bo większość towarów fabrycznie ma taki
kod. Wyszukiwarka na liście przedmiotów szuka teraz też po nich, nie tylko po
nazwie i numerze ewidencyjnym. EAN trafia też do CSV i podpowiadanego opisu
oferty sprzedaży.

## 2026-09-10 — Rozdzielenie sprzedaży: Przygotowane / Wystawione

Zamiast jednej listy mieszającej szkice i eksporty — dwie osobne podstrony:

- `/sprzedaz` — oferty-szkice jeszcze niewyeksportowane
- `/sprzedaz/wystawione` — oferty po eksporcie CSV (status ustawia się
  automatycznie), z akcją „oznacz jako sprzedane” kończącą cykl życia oferty

Przy okazji poprawiony realny gap: eksport CSV teraz zawsze wymusza status
`do_sprzedazy` na przedmiocie, niezależnie od tego, jak powstała jego oferta.

## 2026-09-10 — Widok listy dla przedmiotów

Przełącznik kafelki/lista na `/items` (`?view=grid|list`), zapamiętywany w
sesji — żeby przy większej liczbie przedmiotów dało się przeglądać zwarciej
niż kafelkami ze zdjęciami.

## 2026-09-10 — Dokumentacja projektu

Dodane `CLAUDE.md` (wskazówki techniczne na przyszłość), ten plik i
`TODO.md` — patrz [README.md](README.md#dokumentacja-projektu).

## 2026-09-10 — Ustawienia aplikacji, chronione konto admina

- admin może zmienić logo (upload z podglądem) i nazwę aplikacji —
  `AppSetting`, widoczne od razu w navbarze, na ekranie logowania i w
  tytule strony
- nowa zakładka „Ustawienia” w menu użytkownika zbiera to, co wcześniej
  wisiało osobno w górnym pasku: Użytkownicy i Ustawienia aplikacji (tylko
  admin), Kategorie/Magazyny/Lokalizacje (magazynier+) — górny pasek ma
  teraz tylko Panel/Przedmioty/Sprzedaż
- głównego (zaseedowanego) konta administratora nie da się usunąć ani
  zdegradować/wyłączyć (`User::protected`, celowo poza `$fillable`)

## 2026-09-10 — Ikony w menu

Proste, ręcznie rysowane ikony SVG (`x-icon`, bez zewnętrznej biblioteki) przy
pozycjach głównego menu i na kafelkach w Ustawieniach.

## 2026-09-10 — Wielojęzyczność (EN domyślnie, PL do wyboru)

- angielski jako domyślny język i źródłowe teksty w kodzie (`__('...')` po
  angielsku), polski jako pełne tłumaczenie w `lang/pl.json` — obejmuje
  wszystkie własne widoki, komunikaty flash, etykiety statusów i ról, nie
  tylko standardowe ekrany logowania z Breeze
- `lang/pl/{validation,auth,passwords,pagination}.php` z pakietu
  `laravel-lang/lang` — kompletne polskie komunikaty walidacji
- kolejność ustalania języka: osobista preferencja użytkownika → globalny
  domyślny język ustawiony przez admina (Ustawienia → Ustawienia aplikacji)
  → `APP_LOCALE` z `.env`
- wybór własnego języka w Profilu (obok zmiany hasła) — celowo nie w
  rozwijanym menu, żeby nie rozrastało się przy kolejnych językach

## 2026-09-10 — Nazwa i logo: „Graty”

Robocze „Ewidencja Graty” zwężone do samej marki **Graty** — „Ewidencja” było
opisem, nie nazwą. Domyślne logo (gdy admin nie wgrał własnego w Ustawienia →
Ustawienia aplikacji) to teraz narysowany od zera regał z trzema półkami,
w tej samej stylistyce co ikony menu (`x-icon`), zamiast domyślnego loga
Laravela. Nazwa marki widoczna teraz też jako tekst obok loga w pasku
nawigacji i na ekranie logowania, nie tylko w tytule karty.

## 2026-09-14 — Rebranding na „Craty” pod wydanie open source

„Graty” zmienione na **Craty** przed planowanym udostępnieniem projektu jako
open source — nazwa brzmiąca po polsku nie sprawdziłaby się dobrze
międzynarodowo. „Craty” brzmi niemal identycznie (minimalny koszt
przestawienia się), a dodatkowo nawiązuje do angielskiego „crate” (skrzynka/
pojemnik), pasując tematycznie do magazynu. Sprawdzone przed zmianą: nazwa
wolna na GitHubie, brak kolidujących projektów.

Zmienione: `composer.json` (`name`: `laravel/laravel` → `craty/craty`, plus
realny `description`/`keywords` zamiast domyślnych ze szkieletu Laravela),
`package.json` (dodane brakujące pole `name`), `APP_NAME` w `.env`, domeny
e-mail kont startowych w `DatabaseSeeder` (`@graty.test` → `@craty.test`),
README/CLAUDE.md.

Świadomie **bez zmian**: nazwa/dane bazy dev w `docker-compose.yml`
(`graty`/`graty`/`graty`) — to wewnętrzny, niewidoczny dla użytkownika
appki szczegół techniczny, a zmiana wymagałaby przebudowy kontenera/wolumenu
bazy; oraz nazwy wyświetlane appki już skonfigurowane ręcznie przez adminów
istniejących instalacji w Ustawienia aplikacji (np. „Moje Graty”) — to ich
własna, edytowalna nazwa instancji, nie nazwa projektu.

## 2026-09-14 — Ustawienia poczty/SMTP i moduł Backup (Faza 1)

Dwa fundamenty z `TODO.md`, budowane razem bo drugi zależy pośrednio od
pierwszego (powiadomienia w przyszłości) i oba odblokowują dalsze fazy.

**Ustawienia poczty** (Ustawienia → Poczta, tylko admin): host/port/
szyfrowanie/login/hasło/nadawca SMTP, trzymane w `AppSetting` zamiast tylko
w `.env` (hasło szyfrowane `encrypted` cast — jedyny taki sekret w appce).
Puste ustawienia = appka dalej korzysta z configu z `.env` (np. `MAIL_MAILER=
log` na dev/Dockerze). `MailSettingsApplier` nadpisuje `config('mail...')`
tuż przed wysyłką, nie middleware'em na każde żądanie. Formularz ma przycisk
„wyślij testową wiadomość” — testuje dokładnie to, co wpisane, niekoniecznie
to, co już zapisane, z czytelnym komunikatem błędu połączenia zamiast
suchego wyjątku.

**Moduł Backup** (Ustawienia → Kopie zapasowe, tylko admin): kopia bazy +
`storage/app/public` (zdjęcia, załączniki, logo) do zip-a na osobnym dysku
`backups` (nigdy publicznie eksponowanym), z listą istniejących kopii do
pobrania/usunięcia, przyciskiem „Utwórz kopię teraz” i ustawieniami retencji
(w dniach) oraz opcjonalnego dołączania `.env` (domyślnie wyłączone — zawiera
sekrety). Oparte na `spatie/laravel-backup` zamiast pisania dumpu/zipowania
od zera. `BackupService` to osobna klasa (nie tylko logika w kontrolerze),
żeby dało się ją wywołać programistycznie z przyszłego modułu Aktualizacje
(backup jako krok przed każdą aktualizacją) i z harmonogramu
(`routes/console.php`, `Schedule::call()` zamiast gołych komend `backup:run`/
`backup:clean`, żeby zaplanowane uruchomienia też respektowały ustawienia
admina).

Po drodze: obraz Dockera (`Dockerfile`) potrzebował doinstalowania
`mariadb-client` — `spatie/laravel-backup` woła binarkę `mysqldump` do
zrzutu bazy, a obraz miał tylko rozszerzenie `pdo_mysql` PHP, nie klienta
wiersza poleceń.

13 nowych testów (`MailSettingsTest`, `BackupManagementTest`), 60/60 zielone.

## 2026-09-14 — Publiczna strona "Flea market" (pchli targ)

Jedyna dziś publiczna (bez logowania) część appki: `/flea-market` pokazuje
oferty ze `SaleListing` (status "wyeksportowana") z przełącznikiem lista/
kafle jak w `/items`, filtrem kategorii po lewej i banerem kontaktowym
(e-mail + telefon) na górze zamiast linku przy każdej ofercie — bez
możliwości zakupu, appka niczego nie sprzedaje sama. Sprzedane i wycofane
oferty znikają z listy całkowicie. Włącznik i dane kontaktowe w
Ustawienia → Ustawienia aplikacji, domyślnie wyłączone.

Przy okazji: przycisk "wycofaj" na `/sprzedaz/wystawione` (nowy status
`SaleListing::wycofana`, odróżniony od `Item::STATUSES['wycofany']` —
wycofanie oferty sprzedaży to nie to samo co wycofanie całego przedmiotu
z użytku).

## 2026-09-14 — Instalator webowy

Kreator pod `/install` do stawiania appki na docelowym hostingu bez
SSH/artisan — jednostronicowy formularz (Alpine.js), pyta o wymagania
środowiska, dane do bazy (z testem połączenia), nazwę appki i konto
głównego administratora (chronione tak samo jak dev-owy
`admin@craty.test`), z opcjonalnymi danymi przykładowymi. Blokuje się
automatycznie po zakończeniu — każde ponowne wejście na `/install*`
przekierowuje na `/login`, gdy w bazie istnieje już jakikolwiek użytkownik.

Wymagało dwóch zmian sięgających głębiej niż sam instalator: `APP_KEY`
generowany automatycznie w `public/index.php` zanim Laravel wystartuje (bez
klucza wywala się każde żądanie, nie tylko instalatora), oraz
`.env.example` przełączony z `SESSION_DRIVER=database`/`CACHE_STORE=
database`/`QUEUE_CONNECTION=database` na `file`/`file`/`sync` — appka nie
używa cache'a ani kolejek, a sterownik bazodanowy wymagał tabel, które nie
istnieją przed pierwszą migracją. `DatabaseSeeder` rozdzielony na konta
dev-owe i samodzielny `DemoDataSeeder` (to on jest wywoływany przez checkbox
"dane przykładowe" w kreatorze).

12 nowych testów (`InstallerTest`, `EnvFileWriterTest`) — w tym pełny
przebieg end-to-end na jednorazowej bazie scratch na tym samym MariaDB co
dev, nigdy nie dotykający realnych danych deweloperskich.

## 2026-09-14 — Moduł Aktualizacje

Samo-aktualizacja przez panel (Ustawienia → Aktualizacje) — bez SSH/git/
composera/npm na docelowym hostingu. Administrator wgrywa plik `.zip`
zbudowany w tym repo komendą `php artisan release:build` (pakuje kod razem
z `vendor/` i skompilowanymi assetami, z manifestem `update-manifest.json`:
wersja docelowa, opcjonalne `min_version`, changelog). Opcjonalna suma
kontrolna SHA-256 wklejana z release notes weryfikuje wgrany plik przed
dotknięciem czegokolwiek.

Przed zastosowaniem appka sama robi migawkę obecnego kodu (ten sam
mechanizm co `release:build`) — błędną aktualizację da się cofnąć jednym
przyciskiem, choć to przywraca tylko kod, nie bazę danych (świadomie: ogólne
automatyczne cofanie migracji nie jest bezpieczne). Jeśli aktualizacja
dodała migracje, przywrócenie bazy zostaje ręczne, z automatycznego
backupu wziętego przed aktualizacją. Rozpakowywanie odporne na "zip slip",
`.env`/dane użytkownika nigdy nie są nadpisywane, upload i wycofanie
wymagają ponownego podania hasła (Breeze'owy `password.confirm`).

18 nowych testów, wszystkie operujące na katalogach tymczasowych zamiast na
tym repo — `apply()`/`rollback()` nadpisują pliki appki, więc uruchomienie
ich na prawdziwym drzewie kodu w trakcie testów by je zepsuło.

## 2026-09-15 — Naprawa: brakujące pole hasła w formularzu użytkownika

Zgłoszone przez użytkownika: przy dodawaniu/edycji konta w Ustawienia →
Użytkownicy nie było w ogóle pola do wpisania hasła. Przyczyna: dyrektywa
Blade wewnątrz tagu komponentu `<x-text-input>` (najpierw `@if...@endif`,
sprawdzone też `@required(...)` — to samo) psuje parser tagów Blade w
sposób, który nie rzuca żadnego błędu — cały tag ląduje w HTML-u jako
dosłowny, nieprzetworzony tekst zamiast `<input>`. Poprawka: rozdzielenie na
dwa czyste warianty tagu w `@if`/`@else`. Opisane w CLAUDE.md jako ogólna
pułapka na przyszłość.

## 2026-09-15 — Wymagania dotyczące haseł i podwójne potwierdzenie

Na prośbę użytkownika: hasła w całej appce (zmiana w Profilu, reset hasła,
konto głównego admina z Instalatora, konta zakładane/edytowane przez admina)
muszą mieć teraz co najmniej 8 znaków, wielką i małą literę oraz znak
specjalny — jedna reguła (`Password::defaults()` w
`AppServiceProvider::boot()`), którą wszystkie te miejsca dziedziczą
automatycznie. Formularz użytkownika w Ustawienia → Użytkownicy dostał też
brakujące dotąd pole potwierdzenia hasła (na obu: dodawaniu i edycji).

8 nowych testów (poprawne odrzucanie zbyt krótkich/bez wielkiej litery/bez
znaku specjalnego haseł, niezgodne potwierdzenie, edycja bez podania hasła
zostawia stare bez zmian), 124/124 zielone.

## 2026-09-15/16 — Instalator na realnym hostingu, PWA, tryb ciemny, druk etykiet

Duży pakiet zmian wynikłych z pierwszego prawdziwego wdrożenia poza
Dockerem — część znaleziona dopiero na produkcji, część to nowe funkcje
zamówione po drodze.

**Wdrożenie i moduł Aktualizacje**
- Trzy warianty paczki instalacyjnej: `-full` (klasyczny układ, kod poza
  document rootem), `-hosting` (spłaszczony układ dla hostingów z
  `open_basedir` ograniczonym do `public_html` — nowa komenda
  `release:build-hosting`, ze samo-naprawiającym się symlinkiem
  `storage` i `.htaccess` chroniącym kod/`.env`), `-update` (wgrywana
  przez panel). Każda z sumą kontrolną `.sha256`.
- Realny minimalny PHP appki obniżony do **8.3** (wcześniej `composer
  update` na PHP 8.4 tego kontenera dev cicho podbijał wymaganie do
  8.4.1) — `config.platform.php` w `composer.json` pilnuje tego na
  przyszłość.
- `UpdateService::apply()` woła teraz automatyczny backup bazy
  (`BackupService::run()`) jako pierwszy krok, przed migawką kodu —
  nieudany backup przerywa całą aktualizację.
- Ostrzeżenie na Ustawienia → Aktualizacje, gdy `upload_max_filesize`/
  `post_max_size` serwera jest za niski na paczkę (~27 MB), plus czytelny
  komunikat gdy PHP po cichu utnie za duży upload.

**PWA i skaner**
- Instalowalność (manifest + service worker), skanowanie QR/kodów
  kreskowych kamerą (`@zxing/browser`), szybkie dodanie przedmiotu po
  nietrafionym skanie — rozbudowane później o wybór kategorii/lokalizacji/
  stanu/opisu wprost w tym formularzu (wcześniej tylko zdjęcie+nazwa+kod).

**Wygoda i drobne poprawki**
- **Tryb ciemny** w całej appce — przełącznik w nawigacji, zapamiętywany
  per przeglądarkę, z anty-migotaniowym skryptem; kilka poprawek
  kontrastu znalezionych dopiero na realnym podglądzie (formularze,
  przyciski, wskaźnik kroków instalatora).
- Ikony głównych pozycji menu obok loga w wersji mobilnej — nie trzeba
  już otwierać rozwijanego menu do Panelu/Przedmiotów/Sprzedaży.
- **Import masowy przedmiotów z CSV** (`/items/import`) — dopasowanie
  kategorii/lokalizacji po kodzie; nierozpoznany kod nie odrzuca wiersza,
  tylko czeka na potwierdzenie jako "nieprzypisany".
- **Druk etykiet** przeprojektowany: trzy szablony fizycznych rozmiarów
  naklejek (32×20mm, 35×25mm, 50×30mm) z opcjonalną ceną, wybierane na
  podglądzie przed wydrukiem, plus druk zbiorczy wielu przedmiotów naraz
  (checkboxy na `/items`).
- Pchli targ pokazuje teraz galerię wszystkich zdjęć oferty, nie tylko
  głównego.
- Naprawiona surowa flaga Breeze (`profile-updated` itp.) wyciekająca
  wprost na ekran zamiast przetłumaczonego potwierdzenia.
- Usunięta rola `podglad` (appka ma dziś tylko `admin`/`magazynier`),
  Ustawienia podzielone na podstawowe/zaawansowane, scalone pole
  "Specyfikacja techniczna" z "Opis".

209 testów PHPUnit, wszystkie zielone.

## 2026-09-16 — Naprawa: aktualizacja przez panel na instalacji spłaszczonej

Realne zgłoszenie z produkcji zaraz po wydaniu 1.1.0: wgranie
`craty-1.1.0-update.zip` przez panel na instalacji spłaszczonej (hosting z
`open_basedir`) "nie wgrało wszystkiego" — nowy tryb ciemny miał przycisk,
ale nic się nie przełączało. Przyczyna: `-update.zip` ma klasyczny układ
(osobny katalog `public/`), którego na instalacji spłaszczonej w ogóle
nie ma — skompilowane assety CSS/JS lądowały w martwym, nieużywanym
podkatalogu zamiast nadpisać prawdziwe pliki. Kod PHP/widoki
aktualizowały się poprawnie (leżą na tym samym poziomie w obu układach),
więc problem był niewidoczny na pierwszy rzut oka.

Naprawione właściwie: `UpdateService` wykrywa teraz automatycznie układ
instalacji (obecność `app-storage/`) i dobiera chronione ścieżki oraz
katalog na własne potrzeby (migawki kodu, stan rollbacku) do wykrytego
układu — bez tego update na instalacji spłaszczonej próbowałby pisać
swoje pliki robocze PRZEZ symlink `storage` prosto do katalogu ze
zdjęciami użytkownika. `release:build-hosting` dostał manifest
(`update-manifest.json`) — ta sama paczka `-hosting.zip` służy teraz i do
świeżej instalacji, i jako aktualizacja przez panel na instalacji już
spłaszczonej, więc nie trzeba już ręcznie kopiować plików przez FTP przy
każdej kolejnej aktualizacji takiej instalacji.

2 nowe testy, 211/211 zielone.
