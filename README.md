# Craty

Aplikacja do zarządzania magazynem i sprzętem w pracowni: kategorie, lokalizacje
(magazyn → regał → półka → pojemnik), numery ewidencyjne z kodami QR, kartoteka
przedmiotu (zdjęcia, specyfikacja, wartość, stan), wypożyczenia, historia zmian
i eksport ofert sprzedażowych (OLX/CSV). Domyślny język interfejsu to
angielski, z pełnym tłumaczeniem na polski (patrz `CLAUDE.md` → Localization).

Nazwa marki to „Craty” (wcześniej robocze „Graty”/„Ewidencja Graty” — zmienione
z myślą o wydaniu jako open source, żeby nazwa dobrze brzmiała i była łatwo
wyszukiwalna też dla anglojęzycznych). Instancja może mieć własną nazwę
wyświetlaną — Ustawienia → Ustawienia aplikacji (nazwa + logo), bez ruszania
kodu; np. u siebie masz ustawione „Moje Graty” i to zostaje bez zmian.

Pełna koncepcja i uzasadnienie decyzji: patrz opublikowany dokument
["Ewidencja Graty"](https://claude.ai/code/artifact/1c7498de-ff94-4f0a-a338-0bee7e681bb8)
(tytuł historyczny, treść wciąż aktualna).

To jest **szkielet** (etap 0 + rdzeń etapu 1 z mapy drogowej): historia zmian,
wypożyczenia i eksport CSV już działają; PWA, integracja z Nextcloud i appka
natywna to kolejne etapy.

## Stack

PHP 8.4 (Laravel 12) + MariaDB, Blade + Tailwind, kody QR przez `endroid/qr-code`,
PDF-y przez `barryvdh/laravel-dompdf`. Całość developersko chodzi w Dockerze —
**nie trzeba mieć PHP/Composera zainstalowanych lokalnie**, tylko Docker i Node
(Node jest potrzebny tylko do zbudowania assetów Tailwind/Vite).

## Uruchomienie (dev)

```bash
docker compose up -d          # baza (MariaDB), aplikacja (php artisan serve), Adminer
npm install && npm run build  # raz, żeby zbudować CSS/JS (Vite)
./art migrate:fresh --seed    # baza + dane startowe
```

Aplikacja: http://localhost:8000
Adminer (podgląd bazy): http://localhost:8080 (system: MySQL, serwer: `db`, użytkownik/hasło: `graty`/`graty`, baza: `graty` —
nazwa/dane bazy dev celowo zostały bez zmian przy zmianie marki na Craty; to
wewnętrzny szczegół techniczny niewidoczny dla użytkownika appki, a zmiana
wymagałaby przebudowy kontenera bazy)

Konta startowe (hasło dla wszystkich: `password`):

| E-mail                  | Rola        | Dostęp                                  |
|--------------------------|-------------|------------------------------------------|
| admin@craty.test         | admin       | pełny — w tym zarządzanie użytkownikami   |
| magazynier@craty.test    | magazynier  | dodaje/edytuje przedmioty, lokalizacje... |
| podglad@craty.test       | podglad     | tylko odczyt                              |

Samodzielna rejestracja jest celowo wyłączona — konta zakłada admin w
`/users`.

## Codzienna praca

Zamiast instalować PHP lokalnie, użyj wrapperów w katalogu projektu (uruchamiają
polecenie w kontenerze, jako Twój użytkownik systemowy — nie jako root):

```bash
./art migrate              # każde polecenie artisan
./art make:model Foo -m
./composer require paczka/nazwa
./test                     # testy PHPUnit (osobna baza SQLite w pamięci)
```

Assety frontendowe (Tailwind/Vite) budujemy przez zwykłe `npm run dev` /
`npm run build` na hoście — Node jest zwykle już zainstalowany, więc nie ma
potrzeby robić tego w kontenerze.

## Struktura domenowa

- `app/Models` — Category, Warehouse, StorageLocation, Item (+ Photo/Attachment/History), Loan, SaleListing, User (role: admin/magazynier/podglad)
- `app/Services/InventoryNumberGenerator.php` — buduje numer ewidencyjny (`NAR-M1R3-2026-00042`), z atomowym licznikiem rocznym w `inventory_number_sequences`
- `app/Services/QrCodeGenerator.php` — generuje SVG z kodem QR wskazującym na kartę przedmiotu
- `app/Observers/ItemObserver.php` — zapisuje historię zmian przedmiotu
- `app/Http/Middleware/EnsureUserHasRole.php` — middleware `role:admin,magazynier`

## Dokumentacja projektu

- [CLAUDE.md](CLAUDE.md) — wskazówki techniczne (komendy, architektura,
  pułapki) do pracy nad kodem.
- [CHANGELOG.md](CHANGELOG.md) — historia tego, co i dlaczego powstało.
- [TODO.md](TODO.md) — co jeszcze zostało z mapy drogowej i drobne braki.

Obraz Dockera (`Dockerfile`) jest tylko na potrzeby dewelopmentu (`php artisan
serve`) — wdrożenie produkcyjne (php-fpm + nginx, kolejka, cron, HTTPS) to
osobny temat.

## Wdrożenie na prawdziwym hostingu

Poza dev-loopem z Dockera appka ma webowy instalator pod adresem `/install`
— kreator działa od razu po wgraniu plików na serwer (kopiuje `.env` z
`.env.example`, sam generuje `APP_KEY`), nie wymaga wcześniejszego dostępu
SSH/artisan. Pyta o dane do bazy (z testem połączenia), nazwę appki i konto
głównego administratora, opcjonalnie ładuje dane przykładowe. Po zakończeniu
blokuje się automatycznie — nie da się go uruchomić drugi raz, gdy w bazie
istnieje już jakikolwiek użytkownik.

Kolejne wersje wgrywa się przez panel (Ustawienia → Aktualizacje) jako plik
`.zip` — tak samo bez SSH/composera/npm. Paczki buduje się w tym repo
komendą `php artisan release:build` (pakuje kod wraz z `vendor/` i
skompilowanymi assetami). Przed zastosowaniem appka sama robi migawkę
obecnego kodu, więc błędną aktualizację da się cofnąć jednym przyciskiem
("Wycofaj ostatnią aktualizację") — to przywraca tylko kod, nie bazę danych.
