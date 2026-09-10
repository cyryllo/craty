# Ewidencja Graty

Aplikacja do zarządzania magazynem i sprzętem w pracowni: kategorie, lokalizacje
(magazyn → regał → półka → pojemnik), numery ewidencyjne z kodami QR, kartoteka
przedmiotu (zdjęcia, specyfikacja, wartość, stan), wypożyczenia, historia zmian
i eksport ofert sprzedażowych (OLX/CSV).

Pełna koncepcja i uzasadnienie decyzji: patrz opublikowany dokument
["Ewidencja Graty"](https://claude.ai/code/artifact/1c7498de-ff94-4f0a-a338-0bee7e681bb8).

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
Adminer (podgląd bazy): http://localhost:8080 (system: MySQL, serwer: `db`, użytkownik/hasło: `graty`/`graty`, baza: `graty`)

Konta startowe (hasło dla wszystkich: `password`):

| E-mail                  | Rola        | Dostęp                                  |
|--------------------------|-------------|------------------------------------------|
| admin@graty.test         | admin       | pełny — w tym zarządzanie użytkownikami   |
| magazynier@graty.test    | magazynier  | dodaje/edytuje przedmioty, lokalizacje... |
| podglad@graty.test       | podglad     | tylko odczyt                              |

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

## Znane ograniczenia szkieletu (do etapu 2/3)

- Brak PWA / trybu offline i skanowania QR kamerą — na razie kod QR linkuje do
  strony przedmiotu, którą otwiera zwykła przeglądarka telefonu.
- Eksport CSV do sprzedaży jest uniwersalny (nie ma bezpośredniej integracji z
  API OLX — patrz uzasadnienie w dokumencie koncepcyjnym, sekcja 07).
- Brak importu masowego istniejącego spisu z arkusza.
- Obraz Dockera (`Dockerfile`) jest tylko na potrzeby dewelopmentu (`php artisan
  serve`) — wdrożenie produkcyjne (php-fpm + nginx, kolejka, cron, HTTPS) to
  osobny temat.
