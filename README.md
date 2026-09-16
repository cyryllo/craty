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
- Serwer WWW z obsługą przepisywania adresów (Apache z `mod_rewrite` i
  `AllowOverride All`, albo Nginx z regułą kierującą wszystko do `index.php`)
- Zapis (uprawnienia dla użytkownika PHP) do katalogów `storage/` i
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
ani npm na serwerze — `vendor/` i zbudowany frontend (`public/build`) są
już w środku paczki.

`php artisan release:build*` (uruchamiane w tym repo, nie na hostingu)
produkuje trzy rodzaje paczek do `storage/app/releases/`, każdą pod inny
scenariusz:

| Paczka | Kiedy używać | Jak zainstalować |
|---|---|---|
| **`craty-{wersja}-full.zip`** | Świeża instalacja na hostingu z klasyczną strukturą — da się trzymać kod appki **poza** document rootem (np. VPS, hosting z możliwością ustawienia document rootu na dowolny podkatalog). | Rozpakuj **całość** poza document rootem serwera (np. obok `public_html`), a **zawartość** folderu `public/` z paczki skopiuj **do** document rootu. Wejdź na `/install`. |
| **`craty-{wersja}-hosting.zip`** | Hosting z `open_basedir` ograniczonym do samego document rootu (typowe na cPanel/DirectAdmin) — PHP fizycznie nie ma prawa czytać niczego poza `public_html`, więc rozdzielenie kodu i document rootu jest niemożliwe. Nadaje się **też** jako aktualizacja instalacji już postawionej w tym wariancie (patrz niżej). | **Świeża instalacja:** rozpakuj **całą zawartość wprost do document rootu** (`public_html`) — bez żadnej ręcznej edycji. Paczka ma już poprawione ścieżki w `index.php` i gotowe pliki `.htaccess` blokujące dostęp z przeglądarki do `.env`, kodu źródłowego i `vendor/`. Wejdź na `/install`. **Aktualizacja:** zaloguj się jako admin → Ustawienia → Aktualizacje → wgraj *tę samą* paczkę `-hosting.zip` (nie `-update.zip` — patrz ostrzeżenie niżej). |
| **`craty-{wersja}-update.zip`** | Aktualizacja instalacji w wariancie **`-full`** (klasyczny układ, kod poza document rootem). **Nie używać na instalacji w wariancie `-hosting`** — patrz ostrzeżenie niżej. | Zaloguj się jako admin → Ustawienia → Aktualizacje → wgraj plik. `.env` i dane użytkowników (`storage/app/public`, `storage/logs`) nigdy nie są nadpisywane. |

Wszystkie trzy mają identyczną zawartość kodu — różnią się tylko układem
plików i (w wariancie `hosting`) dodanymi `.htaccess`.

> **Ważne — wybierz paczkę aktualizacji zgodną z układem swojej
> instalacji.** Panel (Ustawienia → Aktualizacje) sam wykrywa, czy dana
> instalacja jest spłaszczona (`-hosting`) czy klasyczna (`-full`), i
> odpowiednio chroni dane użytkownika — ale **nie potrafi zgadnąć układu
> samej wgrywanej paczki**. Jeśli Twoja instalacja jest spłaszczona
> (rozpakowana z `-hosting.zip` prosto do `public_html`), aktualizuj ją
> **zawsze paczką `-hosting.zip`**, nigdy `-update.zip` — ten drugi ma
> osobny katalog `public/`, którego na spłaszczonej instalacji nie ma, więc
> skompilowany frontend (CSS/JS) wyląduje w martwym, nieużywanym
> podkatalogu zamiast nadpisać prawdziwe pliki (kod PHP i widoki
> zaktualizują się poprawnie, bo leżą na tym samym poziomie w obu
> układach — tylko assety spod `public/` nie).

**Skąd wiadomo, który wybrać?** Jeśli nie masz pewności, zacznij od
`-full`. Jeśli po rozpakowaniu i ustawieniu document rootu appka nie
startuje z błędem `open_basedir restriction in effect` — to znak, że
hosting wymaga wariantu `-hosting`.

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

- [CHANGELOG.md](CHANGELOG.md) — historia zmian
- [TODO.md](TODO.md) — mapa drogowa

## Licencja

[MIT](LICENSE)
