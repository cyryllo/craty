# Craty

*[Read this in English →](README.en.md)*

Prosta aplikacja do ewidencji sprzętu i magazynu w warsztacie/pracowni:
kategorie, lokalizacje (magazyn → regał → półka → pojemnik), automatyczne
numery ewidencyjne z kodami QR, kartoteka przedmiotu (zdjęcia, specyfikacja,
wartość, stan), wypożyczenia, historia zmian i eksport ofert sprzedażowych
do CSV (OLX). Do tego publiczna strona „pchli targ” dla wystawionych ofert,
webowy instalator i moduł samo-aktualizacji przez panel administratora.

Do tego appka instaluje się jako PWA na telefonie/tablecie i skanuje kody QR
oraz kreskowe (EAN/UPC) wprost kamerą przeglądarki — nietrafiony skan
proponuje szybkie dodanie nowego przedmiotu.

Zbudowana na Laravel 12 + MariaDB, w pełni dwujęzyczna (PL/EN).

> Skąd nazwa? „Craty” to połączenie polskiego „Graty” (sprzęt/rupiecie w
> warsztacie) i angielskiego „crate” (skrzynka) — appka zaczynała jako
> wewnętrzne narzędzie „Graty” i zachowała podobne brzmienie przy zmianie
> nazwy na wersję open source.

## Wymagania (własny hosting)

Dotyczy instalacji poza Dockerem (patrz [Wdrożenie na hostingu](#wdrożenie-na-hostingu-bez-dockera-i-ssh)
niżej) — kreator instalacji (`/install`) sam sprawdza to wszystko i pokazuje,
czego ewentualnie brakuje, zanim pozwoli przejść dalej.

- **PHP ≥ 8.3**
- Rozszerzenia PHP: `pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`, `exif`, `intl`
- **MySQL 8+ lub MariaDB 10.3+**
- **Apache z `mod_rewrite` i `AllowOverride All`.** Appka nie ma osobnego
  katalogu `public/` — cały kod (w tym `.env`) leży w tym samym katalogu co
  `index.php`, a jego ochrona przed dostępem z przeglądarki zależy
  wyłącznie od reguł w `.htaccess`. Bez `AllowOverride All` (domyślne
  ustawienie wielu hostingów to `AllowOverride None`) Apache po cichu
  ignoruje te reguły i cały kod źródłowy staje się publicznie pobieralny.
  Nginx nie czyta `.htaccess` w ogóle, więc wymagałby ręcznego przepisania
  tych reguł na konfigurację Nginx — appka nie dostarcza tego gotowego.
- Zapis (uprawnienia dla użytkownika PHP) do katalogów `app-storage/` i
  `bootstrap/cache/`
- **HTTPS** — wymagany do skanowania kamerą (PWA) i realnie zalecany zawsze;
  zwykły HTTP na produkcji przeglądarki traktują jako niebezpieczny
  kontekst i blokują dostęp do kamery

## Szybki start (Docker)

```bash
git clone https://github.com/cyryllo/craty.git
cd craty
cp .env.example .env
docker compose up -d
npm install && npm run build
./art migrate --seed
```

Aplikacja: http://localhost:8000 — konta startowe (hasło: `password`):
`admin@craty.test`, `magazynier@craty.test`.

**Własne dane logowania do bazy:** przed pierwszym `docker compose up`
zmień `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` (i opcjonalnie
`DB_ROOT_PASSWORD`) w pliku `.env` — Docker Compose czyta ten sam plik, więc
kontener MariaDB założy bazę/użytkownika z tymi wartościami. Zmiana tego
już PO pierwszym uruchomieniu (gdy wolumen bazy istnieje) nic nie da — trzeba
wtedy usunąć wolumen (`docker compose down -v`) i wystartować od nowa.

## Wdrożenie na hostingu (bez Dockera i SSH)

Appka ma webowy kreator instalacji pod `/install` — sam generuje `.env`,
prosi o dane do bazy i zakłada konto administratora. Nie trzeba composera
ani npm na serwerze — `vendor/` i zbudowany frontend (`build/`) są już w
środku paczki.

`php artisan release:build` (uruchamiane w tym repo, nie na hostingu)
produkuje **jedną** paczkę do `app-storage/app/releases/craty-{wersja}.zip`
— appka nie ma osobnego katalogu `public/` (patrz `.htaccess`/`index.php`
w korzeniu repo), więc ta sama paczka służy zarówno do świeżej instalacji,
jak i do aktualizacji już postawionej instalacji przez panel:

- **Świeża instalacja:** rozpakuj **całą zawartość wprost do document
  rootu** (`public_html` albo odpowiednik u Twojego hostingu) — bez żadnej
  ręcznej edycji. Paczka ma już gotowy `index.php` i pliki `.htaccess`
  blokujące dostęp z przeglądarki do `.env`, kodu źródłowego i `vendor/`.
  Wejdź na `/install`.
- **Aktualizacja:** zaloguj się jako admin → Ustawienia → Aktualizacje →
  wgraj tę samą paczkę. `.env` i dane użytkowników (`app-storage/app/
  public`, `app-storage/logs`, symlink `storage`) nigdy nie są nadpisywane.

**Limit rozmiaru wgrywanego pliku:** paczka to zwykle kilkanaście–
kilkadziesiąt MB (zawiera cały `vendor/` i skompilowany frontend), a
domyślne limity PHP (`post_max_size`, zwykle 8M; `upload_max_filesize`,
zwykle 2M) tego nie przepuszczą — upload padnie z błędem `413 Content Too
Large`, zanim żądanie w ogóle dotrze do aplikacji. Obraz Dockera z tego
repo ma to już podniesione (`128M`), ale na zwykłym hostingu trzeba
samodzielnie podbić obie wartości w `php.ini` (albo w `.htaccess`/panelu
hostingu, jeśli nie ma dostępu do `php.ini`) i zrestartować PHP/serwer.

**Skanowanie kamerą wymaga HTTPS** (albo `localhost`, stąd działa bez
niczego dodatkowego na dev) — przeglądarki nie dają dostępu do kamery na
zwykłym HTTP w produkcji. Zadbaj o certyfikat, zanim ktoś się zdziwi, że
„skaner nie działa”.

## Dokumentacja

- [INSTRUKCJA.md](INSTRUKCJA.md) — instrukcja użytkownika (główne funkcje,
  struktura magazynowa)
- [CHANGELOG.md](CHANGELOG.md) — historia zmian

## Licencja

[MIT](LICENSE)
