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

## Requirements (self-hosting)

Applies to installs outside Docker (see
[Deploying to a hosting account](#deploying-to-a-hosting-account-no-docker-or-ssh-needed)
below) — the installer (`/install`) checks all of this itself and shows
what's missing before letting you proceed.

- **PHP ≥ 8.3**
- PHP extensions: `pdo_mysql`, `mbstring`, `gd`, `zip`, `bcmath`, `exif`, `intl`
- **MySQL 8+ or MariaDB 10.3+**
- A web server with URL rewriting (Apache with `mod_rewrite` and
  `AllowOverride All`, or Nginx with a rule routing everything to `index.php`)
- Write access (for the PHP user) to `storage/` and `bootstrap/cache/`
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
frontend (`public/build`) already ship inside the package.

`php artisan release:build*` (run in this repo, not on the target host)
produces three kinds of packages under `storage/app/releases/`, one per
scenario:

| Package | When to use | How to install |
|---|---|---|
| **`craty-{version}-full.zip`** | Fresh install on hosting with a classic layout — you can keep the app code **outside** the document root (VPS, or a host that lets you point the document root at an arbitrary subfolder). | Extract **everything** outside the document root (e.g. next to `public_html`), then copy the **contents** of the package's `public/` folder **into** the document root. Visit `/install`. |
| **`craty-{version}-hosting.zip`** | Hosting with `open_basedir` restricted to the document root itself (typical on cPanel/DirectAdmin) — PHP has no permission to read anything outside `public_html`, so separating code from the document root is impossible. | Extract the **entire contents straight into the document root** (`public_html`) — no manual editing needed. The package already has `index.php`'s paths fixed and ready-made `.htaccess` files blocking browser access to `.env`, the source code, and `vendor/`. Visit `/install`. |
| **`craty-{version}-update.zip`** | Updating an already-installed app (either variant above). | Log in as admin → Settings → Updates → upload the file. `.env` and user data (`storage/app/public`, `storage/logs`) are never overwritten. |

All three contain identical application code — they only differ in file
layout (and, for the `hosting` variant, the added `.htaccess` files).

**Which one should I pick?** If unsure, start with `-full`. If the app
fails to boot with an `open_basedir restriction in effect` error after you
set the document root, that's the sign you need the `-hosting` variant
instead.

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
- [TODO.md](TODO.md) — roadmap

## License

[MIT](LICENSE)
