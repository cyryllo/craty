# Craty

*[Przeczytaj po polsku →](README.md)*

A simple inventory/asset management app for a workshop: categories,
locations (warehouse → rack → shelf → bin), auto-generated inventory
numbers with QR codes, item records (photos, specs, value, condition),
loans, change history, and CSV export of sale listings (OLX). Also includes
a public "flea market" page for listed items, a web-based installer, and a
self-update module via the admin panel.

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
`admin@craty.test`, `magazynier@craty.test`, `podglad@craty.test`.

For a real hosting deployment (no Docker or SSH needed), the app ships a
web-based installer at `/install` that generates `.env` for you, asks for
database credentials, and creates the administrator account. Later
versions are uploaded through the admin panel (Settings → Updates) as a
`.zip` package — no Composer or npm required on the server.

## Documentation

- [CLAUDE.md](CLAUDE.md) — architecture and technical notes
- [CHANGELOG.md](CHANGELOG.md) — change history
- [TODO.md](TODO.md) — roadmap

## License

[MIT](LICENSE)
