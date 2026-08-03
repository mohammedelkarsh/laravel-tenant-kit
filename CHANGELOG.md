# Changelog

All notable changes to this project are documented here.

## [1.4.0] — 2026-08-03

### Added

- **Optional KYC (Phase A+B)** — `KYC_ENABLED` flag, `docs/kyc.md`, tenant migration `kyc_verifications`, onboarding at `/kyc`, workspace Filament review panel at `/workspace`
- **Tenant KYC service** — per-workspace settings (`kyc_country`, `kyc_level`, `kyc_extraction_driver`) and tenant-aware verify/queue via `TenantKycService`
- Opt-in package: `composer require kyc-ai/laravel:^1.1.1` (listed under Composer `suggest` only — not a hard dependency)
- **Renovate** — `renovate.json` for automated dependency update PRs
- **Laragon docs** — `docs/laragon.md` comparison vs Docker (port 80 vs 8080) from community PR #4
- Docker PHP image installs **gd** (image uploads / KYC test fixtures)

### Fixed

- KYC feature tests use MIME-based fake uploads (no GD required in CI)

## [1.3.1] — 2026-06-11

### Added

- **In-app guided agent** — floating chat widget on the central domain (dashboard, Filament, landing when logged in); sequential menus and step-by-step flows (create workspace, usage, subscription, invite member); confirm Yes/No chips for dangerous actions
- **api-operator proxy** — `POST /api-operator/chat` and `GET /api-operator/status`; server-side Sanctum token via `ApiOperatorClient` (tokens never exposed to the browser)
- **api-operator integration** — [docs/api-operator.md](docs/api-operator.md) (install, tokens, CLI, HTTP server, Docker `operator` profile)
- **Adapter guide** — expanded `integrations/api-operator/README.md`; synced tools with [api-operator](https://pypi.org/project/api-operator/) v0.10.0
- **Docker scripts** — `scripts/docker-setup.ps1` / `.sh` (first-time) and `scripts/docker-up.ps1` / `.sh` (daily start with operator)
- **i18n** — guided agent menus and flow copy in `lang/en/app.php` and `lang/ar/app.php`
- **Tests** — `ApiOperatorChatTest` (proxy, confirm status, widget HTML, built assets) and `ApiOperatorAdapterTest`

### Changed

- **README** — AI operator section, new screenshot (`api-operator-chat.png`), updated demo GIF
- **Filament** — loads `api-operator-widget.js` via Vite render hook when operator is enabled

## [1.3.0] — 2026-06-14

### Added

- **Usage-based billing** — track workspace meters (`api_calls`, `team_seats`) per calendar month in `usage_records`
- **API** — `GET /api/workspaces/{id}/usage` returns current period usage; subscription endpoint includes usage summary
- **Middleware** — authenticated API requests increment `api_calls` for the resolved workspace
- **Billing UI** — usage section on `/billing/{tenant}` when `USAGE_BILLING_ENABLED=true`
- **Stripe optional sync** — set `USAGE_SYNC_TO_STRIPE=true` to forward meter events via Cashier `reportMeterEvent()`
- **api-operator adapter** — `get_usage` tool in `integrations/api-operator/adapter.yaml`

## [1.2.3] — 2026-06-14

### Fixed

- **CI smoke test** — run Filament `/admin/tenants` via isolated `check-tenants-page.php` subprocess (avoids polluted in-process kernel state on fresh install)

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
