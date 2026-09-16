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

> Where does the name come from? "Craty" blends the Polish "Graty"
> (workshop odds and ends/equipment) with the English "crate" — the app
> started out as an internal tool called "Graty" and kept a similar sound
> when it was renamed for the open-source release.

## Requirements (self-hosting)

Applies to installs outside Docker (see
[Deploying to a hosting account](#deploying-to-a-hosting-account-no-docker-or-ssh-needed)
below) — the installer (`/install`) checks all of this itself and shows
what's missing before letting you proceed.

- **PHP ≥ 8.3**
- PHP extensions: `pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`, `exif`, `intl`
- **MySQL 8+ or MariaDB 10.3+**
- **Apache with `mod_rewrite` and `AllowOverride All`.** The app has no
  separate `public/` directory — all the code (including `.env`) lives in
  the same directory as `index.php`, and keeping it off the browser depends
  entirely on the rules in `.htaccess`. Without `AllowOverride All` (many
  hosts default to `AllowOverride None`), Apache silently ignores those
  rules and the entire source code becomes publicly downloadable. Nginx
  doesn't read `.htaccess` at all, so it would need those rules hand-ported
  to Nginx config — the app doesn't ship a ready-made one.
- Write access (for the PHP user) to `app-storage/` and `bootstrap/cache/`
- **HTTPS** — required for camera scanning (PWA) and recommended generally;
  browsers treat plain HTTP in production as an insecure context and block
  camera access

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

## Deploying to a hosting account (no Docker or SSH needed)

The app ships a web-based installer at `/install` that generates `.env`
for you, asks for database credentials, and creates the administrator
account. No Composer or npm needed on the server — `vendor/` and the built
frontend (`build/`) already ship inside the package.

`php artisan release:build` (run in this repo, not on the target host)
produces **one** package at `app-storage/app/releases/craty-{version}.zip`
— the app has no separate `public/` directory (see the `.htaccess`/
`index.php` at the repo root), so the same package works both for a fresh
install and for updating an already-running install through the panel:

- **Fresh install:** extract the **entire contents straight into the
  document root** (`public_html` or your host's equivalent) — no manual
  editing needed. The package already has `index.php` ready and `.htaccess`
  files blocking browser access to `.env`, the source code, and `vendor/`.
  Visit `/install`.
- **Update:** log in as admin → Settings → Updates → upload the same
  package. `.env` and user data (`app-storage/app/public`, `app-storage/
  logs`, the `storage` symlink) are never overwritten.

Each `.zip` ships with a `.sha256` sidecar file (`sha256sum -c` compatible)
next to it, so you can verify the download without copying the hash from
the console by hand.

**Upload size limit:** a package is typically several to a few dozen MB
(it bundles the full `vendor/` directory and the built frontend), and
PHP's default limits (`post_max_size`, usually 8M; `upload_max_filesize`,
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

## License

[MIT](LICENSE)
