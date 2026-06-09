# Docker development environment

Run the full stack (PHP 8.4, Nginx, MySQL, Redis) without Laragon or Valet.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
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

Open **http://laravel-tenant-kit.test:8080**

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
