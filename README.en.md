# Craty

*[Przeczytaj po polsku →](README.md)*

A simple inventory/asset management app for a workshop: categories,
locations (warehouse → rack → shelf → bin), auto-generated inventory
numbers with QR codes, item records (photos, specs, value, condition),
loans, change history, and CSV export of sale listings (OLX). Also includes
a public "flea market" page for listed items, a web-based installer, and a
self-update module via the admin panel.

It also installs as a PWA on your phone/tablet and scans QR and barcodes
(EAN/UPC) straight from the browser's camera — a miss suggests quickly
adding the item instead of a dead end.

Built with Laravel 12 + MariaDB, fully bilingual (EN/PL).

## Quick start (Docker)

```bash
git clone https://github.com/cyryllo/craty.git
cd craty
cp .env.example .env
docker compose up -d
npm install && npm run build
./art migrate --seed
```

App: http://localhost:8000 — seeded accounts (password: `password`):
`admin@craty.test`, `magazynier@craty.test`.

**Custom database credentials:** before the first `docker compose up`,
change `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` (and optionally
`DB_ROOT_PASSWORD`) in `.env` — Docker Compose reads the same file, so the
MariaDB container is created with those values. Changing them *after* the
first run has no effect (the database volume already exists) — remove it
first with `docker compose down -v` and start over.

For a real hosting deployment (no Docker or SSH needed), the app ships a
web-based installer at `/install` that generates `.env` for you, asks for
database credentials, and creates the administrator account. Later
versions are uploaded through the admin panel (Settings → Updates) as a
`.zip` package — no Composer or npm required on the server.

**Upload size limit:** an update package is typically several to a few
dozen MB (it bundles the full `vendor/` directory and the built frontend),
and PHP's default limits (`post_max_size`, usually 8M; `upload_max_filesize`,
usually 2M) will reject it outright with a `413 Content Too Large` before
the request ever reaches the app. This repo's Docker image already raises
both to `128M`, but on plain hosting you'll need to bump them yourself in
`php.ini` (or via `.htaccess`/your host's control panel if you can't touch
`php.ini`) and restart PHP/the web server.

**Camera scanning needs HTTPS** (or `localhost`, which is why it works out
of the box in dev) — browsers won't grant camera access over plain HTTP in
production. Sort out a certificate before someone wonders why "the scanner
doesn't work".

## Documentation

- [CHANGELOG.md](CHANGELOG.md) — change history
- [TODO.md](TODO.md) — roadmap

## License

[MIT](LICENSE)
