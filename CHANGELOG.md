# Changelog

All notable changes to this project are documented here.

## [1.2.2] — 2026-06-14

### Fixed

- **CI smoke test** — run Filament `/admin/tenants` check before API suspend mutations so the fresh-install path matches a real admin visit (fixes GitHub Actions `system-test.php` 41/42 failure)

## [1.2.1] — 2026-06-10

### Added

- **API rate limiting** — `POST /api/auth/token` limited to 5 attempts per minute per IP (configurable via `API_AUTH_RATE_LIMIT` / `API_AUTH_RATE_DECAY`)
- **Sanctum token abilities** — optional `abilities` array when issuing tokens; scoped middleware on API routes
- **Central API** — `GET /api/workspaces/{id}/subscription` returns Stripe subscription details
- **Tenant API** — `POST /api/team/invitations` to invite teammates (owner/admin)
- **Workspace suspension** — suspend/unsuspend from Filament; suspended workspaces return 403 (API) or 503 (web)

### Changed

- Workspace API responses now include `suspended` status
- Token responses include granted `abilities`

### Fixed

- Docker PHP image now includes the **intl** extension (required by Filament `/admin/tenants`)
- Docker entrypoint self-heals missing `intl` on container start; `entrypoint.sh` mounted in compose
- `ext-intl` declared in `composer.json`; CI and smoke tests verify it is loaded
- **Workspace URLs** include the correct port when using Docker on `:8080` (`TenantUrls`, `APP_PORT_ALT`, Filament tenants table, landing/demo links)
- `docs/docker.md` clarifies Laragon (port 80) vs Docker (port 8080) — do not mix `.env.docker` with Laragon browsing
- System smoke test uses `http://nginx` as default HTTP base inside Docker Compose

## [1.2.0] — 2026-06-09

- OAuth (Google & GitHub) on central app
- Sanctum API (central + tenant)
- SaaS analytics widgets in Filament
- Docker Compose, PostgreSQL, Redis tenant isolation
- 35 PHPUnit tests + 36-point smoke test script
