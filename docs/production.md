# Production deployment (PostgreSQL-first)

**Recommended production database:** PostgreSQL 16+ with Stancl’s database-per-tenant model.  
MySQL remains supported for local Laragon / quick demos; prefer Postgres for multi-tenant SaaS at scale (isolation, concurrent DDL, tooling).

## Recommended stack

| Layer | Choice |
|-------|--------|
| App | PHP 8.4 · Laravel · queue workers |
| Database | **PostgreSQL** (one DB per workspace) |
| Cache / queue / sessions | Redis with tenancy Redis bootstrapper |
| Edge | Nginx (or your platform’s reverse proxy) |
| Agent (optional) | `api-operator` sidecar |

## Docker Compose (production-shaped)

Use the `pgsql` profile (and `operator` if you want the guided agent):

```bash
docker compose --profile pgsql --profile operator up -d --build
```

Copy env and point at Postgres:

```bash
docker compose exec app cp .env.docker .env
# or use .env.docker.pgsql as a starting point
```

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel_tenant_kit
DB_USERNAME=laravel
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
TENANCY_USE_REDIS_BOOTSTRAPPER=true
```

Then:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose --profile build run --rm node
```

Run a **queue worker** (and scheduler) in production:

```bash
php artisan queue:work --queue=default
php artisan schedule:work
```

Platform webhooks and usage gauge snapshots depend on the queue/scheduler when enabled.

## Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`
- [ ] Postgres backups (central + tenant DBs) scheduled
- [ ] Redis persistence / eviction policy understood
- [ ] HTTPS + trusted proxies configured
- [ ] `PLATFORM_WEBHOOKS_*` / `PLAN_GATING_*` only if you need them
- [ ] Stripe live keys + webhook endpoint if billing is live
- [ ] Separate worker process(es) for queues

## Related

- [docker.md](docker.md) — full Compose profiles (MySQL default for local, `pgsql` for Postgres)
- [laragon.md](laragon.md) — Windows local MySQL path
- [webhooks.md](webhooks.md) · [plan-gating.md](plan-gating.md) · [api-operator.md](api-operator.md)
