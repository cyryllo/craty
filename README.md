# Craty

*[Read this in English →](README.en.md)*

Prosta aplikacja do ewidencji sprzętu i magazynu w warsztacie/pracowni:
kategorie, lokalizacje (magazyn → regał → półka → pojemnik), automatyczne
numery ewidencyjne z kodami QR, kartoteka przedmiotu (zdjęcia, specyfikacja,
wartość, stan), wypożyczenia, historia zmian i eksport ofert sprzedażowych
do CSV (OLX). Do tego publiczna strona „pchli targ” dla wystawionych ofert,
webowy instalator i moduł samo-aktualizacji przez panel administratora.

Zbudowana na Laravel 12 + MariaDB, w pełni dwujęzyczna (PL/EN).

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
`admin@craty.test`, `magazynier@craty.test`, `podglad@craty.test`.

**Własne dane logowania do bazy:** przed pierwszym `docker compose up`
zmień `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` (i opcjonalnie
`DB_ROOT_PASSWORD`) w pliku `.env` — Docker Compose czyta ten sam plik, więc
kontener MariaDB założy bazę/użytkownika z tymi wartościami. Zmiana tego
już PO pierwszym uruchomieniu (gdy wolumen bazy istnieje) nic nie da — trzeba
wtedy usunąć wolumen (`docker compose down -v`) i wystartować od nowa.

Na docelowym hostingu (bez Dockera i SSH) appka ma webowy kreator
instalacji pod `/install` — sam generuje `.env`, prosi o dane do bazy i
zakłada konto administratora. Kolejne wersje wgrywa się przez panel
(Ustawienia → Aktualizacje) jako paczkę `.zip`, bez composera/npm na
serwerze.

## Dokumentacja

- [CLAUDE.md](CLAUDE.md) — architektura i wskazówki techniczne
- [CHANGELOG.md](CHANGELOG.md) — historia zmian
- [TODO.md](TODO.md) — mapa drogowa

## Licencja

[MIT](LICENSE)
