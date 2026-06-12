# Docker development environment

Run the full stack (PHP 8.4, Nginx, MySQL, Redis) without Laragon or Valet.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- After changing `docker/php/Dockerfile`, rebuild: `docker compose up -d --build`
- Add to your hosts file:

```
127.0.0.1 laravel-tenant-kit.test
127.0.0.1 demo.laravel-tenant-kit.test
```

## Quick start (MySQL)

```bash
docker compose up -d --build
docker compose exec app cp .env.docker .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose --profile build run --rm node
```

Open **http://laravel-tenant-kit.test** (port 80) or **http://laravel-tenant-kit.test:8080**

Demo workspace: **http://demo.laravel-tenant-kit.test/login** (or same with `:8080`)

> On Windows with **Laragon on port 80**, Docker is usually on **8080** only. Use **`:8080`** on every URL (`http://laravel-tenant-kit.test:8080`, `http://demo.laravel-tenant-kit.test:8080`).  
> Do **not** open tenant URLs without a port while `.env` has `DB_HOST=mysql` — Laragon will serve PHP and fail to resolve the Docker hostname.  
> If you want Laragon without a port, copy `.env.example` to `.env`, run `php artisan migrate --seed` against Laragon MySQL, and use Laragon only (not Docker for browsing).

### `/admin/tenants` shows "intl extension required"

Filament needs the PHP **intl** extension. If you see a 500 error:

```bash
docker compose restart app
docker compose exec app php scripts/check-tenants-page.php
```

Expected output: `intl_loaded=yes` and `status=200`.

If `intl_loaded=no`, rebuild the app image (includes intl permanently):

```bash
docker compose build app
docker compose up -d --force-recreate app
```

Then hard-refresh the browser (Ctrl+Shift+R) and sign in to `/admin` again.

### Slow on Windows?

Docker bind-mounts from `D:\` are slow (15–40s per page is common). Options:

1. **Use Laragon** for daily browsing (fast) and Docker only for CI/parity checks.
2. **Move the repo into WSL2** (`\\wsl$\...`) before `docker compose up`.
3. After code changes in Docker, run `docker compose restart app` (OPcache skips file stat).

Default credentials are the same as local Laragon setup (see README).

## Useful commands

```bash
docker compose exec app php artisan tenant:provision acme "Acme Corp" --admin=boss@acme.com
docker compose exec app php scripts/system-test.php
docker compose logs -f app
docker compose down
```

## PostgreSQL variant

Start with Postgres instead of MySQL:

```bash
docker compose --profile pgsql up -d --build
docker compose exec app cp .env.docker .env
```

Edit `.env` inside the container (or locally):

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_tenant_kit
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Then migrate:

```bash
docker compose exec app php artisan migrate --seed
```

Stancl Tenancy creates isolated databases per workspace on PostgreSQL automatically.

## Redis & tenant isolation

`.env.docker` enables:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
TENANCY_USE_REDIS_BOOTSTRAPPER=true
```

This prefixes Redis keys per tenant so cache/queue data never leaks between workspaces.

## Ports

| Service | Default port |
|---------|--------------|
| Web (Nginx) | 8080 |
| MySQL | 3306 |
| Redis | 6379 |
| PostgreSQL | 5432 (profile `pgsql`) |

Override with `APP_PORT`, `FORWARD_DB_PORT`, etc. in a `.env` file at project root (Docker Compose reads these).

## New workspace subdomains

Add a hosts entry for each new workspace:

```
127.0.0.1 acme.laravel-tenant-kit.test
```

Nginx is configured for wildcard `*.laravel-tenant-kit.test`.
