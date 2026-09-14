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
