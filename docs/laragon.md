# Laragon development environment

Run the full stack (PHP, Nginx, MySQL) using [Laragon](https://laragon.org/) — auto HTTPS, port 80, no Docker overhead.

## Prerequisites

- [Laragon](https://laragon.org/download/) (full edition — includes PHP, MySQL, Nginx/Apache, Composer, Node.js)
- [Git](https://git-scm.com/)

## Quick start

```bash
git clone https://github.com/mohammedelkarsh/laravel-tenant-kit.git
cd laravel-tenant-kit
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate && php artisan db:seed && npm run build
```

Add to your hosts file:

```
127.0.0.1 laravel-tenant-kit.test
127.0.0.1 demo.laravel-tenant-kit.test
```

Open **http://laravel-tenant-kit.test** — done.

> No port needed. Laragon serves on port **80** by default.

## Laragon vs Docker

| Feature | Laragon | Docker |
|---------|---------|-------|
| URL port | **80** (no port in URL) | **8080** (`:8080` required) |
| `.env` file | `.env.example` → `.env` | `.env.docker` → `.env` |
| `APP_URL` | `http://laravel-tenant-kit.test` | `http://laravel-tenant-kit.test:8080` |
| `DB_HOST` | `127.0.0.1` | `mysql` (Docker service name) |
| `DB_USERNAME` | `root` | `root` |
| `DB_PASSWORD` | *(blank / your local password)* | `secret` |
| Cache / Queue | `database` (default) | `redis` |
| Startup time | Instant | 30–60s (container build) |
| Disk performance | Native (fast) | Slow on Windows (`D:\` bind-mounts) |
| Best for | Daily development | CI parity, production testing |

## When to use which `.env`

### Laragon — use `.env.example`

```bash
cp .env.example .env
```

The `.env.example` is configured for local Laragon:
- `DB_HOST=127.0.0.1` points to your local MySQL
- `APP_URL` has no port (uses port 80)
- Cache and queue use `database` driver (no Redis required)

### Docker — use `.env.docker`

```bash
cp .env.docker .env
```

The `.env.docker` is configured for the Docker stack:
- `DB_HOST=mysql` points to the Docker MySQL container
- `APP_URL` includes `:8080`
- Cache and queue use Redis (included in the Docker stack)

> **Don't** copy `.env.docker` when using Laragon — `DB_HOST=mysql` will fail because there's no container named `mysql` on your local machine.

## Hosts file

Both setups need the same hosts entries. The only difference is the port in the URL.

**File location:**
- Windows: `C:\Windows\System32\drivers\etc\hosts`
- macOS / Linux: `/etc/hosts`

**Add these lines:**

```
127.0.0.1 laravel-tenant-kit.test
127.0.0.1 demo.laravel-tenant-kit.test
```

**For Docker**, open `http://laravel-tenant-kit.test:8080`.  
**For Laragon**, open `http://laravel-tenant-kit.test` (no port).

Add entries for any new workspace subdomains too:

```
127.0.0.1 acme.laravel-tenant-kit.test
```

## Common errors

### Tenant 404 or redirect loop

Opening `http://demo.laravel-tenant-kit.test` (no port) while using Docker.

**Fix:** Use `http://demo.laravel-tenant-kit.test:8080` when running Docker. Laragon users do **not** need the port.

### `DB_HOST=mysql` connection refused

Copied `.env.docker` but running Laragon.

**Solution:** Copy `.env.example` instead:

```bash
cp .env.example .env
php artisan key:generate
```

Or manually change `DB_HOST=mysql` → `DB_HOST=127.0.0.1` and `DB_PASSWORD=secret` → your local MySQL password.

### `/admin` 500 error after login

```bash
php artisan optimize:clear && php artisan view:cache
```

### No CSS on tenant domain

Run `npm run build` to compile frontend assets.

### "intl extension required" in Docker

This only affects Docker users. See [docker.md](docker.md#admintenants-shows-intl-extension-required).

### Slow page loads on Windows (Docker)

Docker bind-mounts from `D:\` can be 15–40s per page. Switch to Laragon for daily development — it's significantly faster.
