# Agendaí

Multi-tenant scheduling SaaS for small service businesses (salons, clinics, studios): each
business gets its own public booking page, staff, services and upfront payments.

> **Status:** M0 (foundation). See the [spec and roadmap](docs/ESPEC.md) (in Portuguese).

**Stack:** Laravel 13 · PHP 8.4 · Inertia + React 19 + TypeScript · Tailwind 4 · PostgreSQL 17 (Neon) · Vercel

## Run locally

Requires Docker.

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm ci
```

- App: http://localhost:8000
- Mailpit (captured e-mails): http://localhost:8025

## Quality checks

```bash
docker compose exec app composer test      # Pint, Larastan and the test suite (on PostgreSQL)
docker compose exec app npm run types:check
```

Tests run on PostgreSQL, not SQLite: the double-booking guarantee is a Postgres exclusion
constraint, and only the real database can prove it.

## Deploy

Every push to `main` runs the checks in GitHub Actions. If they pass, the same workflow runs the
migrations on Neon and deploys to Vercel.

The build happens in CI rather than on Vercel because Wayfinder generates the typed frontend
routes with `php artisan`, and Vercel's build image has no PHP. At runtime the app is a single
`vercel-php` function (`api/index.php`) in São Paulo (`gru1`), next to the Neon database.
Storage is moved to `/tmp` because the deployed filesystem is read-only, and the Neon endpoint
ID is passed in the password because the runtime's libpq doesn't send SNI
(see `config/database.php`).
