# KYC (optional)

Identity verification for workspaces — **opt-in**, not required to run Tenant Kit.

Package: [kyc-ai/laravel](https://packagist.org/packages/kyc-ai/laravel) · [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai)

See [ROADMAP](ROADMAP.md) — v1.4.0.

## Install (adopters)

```bash
composer require kyc-ai/laravel:^1.1.1
```

Optional country validators:

```bash
composer require validators/ae validators/eg
```

For **full** level (external provider), also install the Shufti driver:

```bash
composer require kyc-ai/external-shufti
php artisan vendor:publish --tag=kyc-shufti-config
```

Do **not** add `kyc-ai/laravel` (or satellite drivers) to Tenant Kit’s default `composer.json` `require`.

## Enable

```env
KYC_ENABLED=true
KYC_AUDIT_ENABLED=true
KYC_LEVEL=standard
KYC_EXTRACTION_DRIVER=fake
KYC_DEFAULT_COUNTRY=sa
KYC_EXTERNAL_ENABLED=false
```

Then migrate **tenant** databases (Stancl). The `kyc_verifications` migration already lives in `database/migrations/tenant/`:

```bash
php artisan tenants:migrate
```

## What Tenant Kit wires

| Piece | Behavior |
|-------|----------|
| `App\Support\Kyc::ready()` | `KYC_ENABLED` **and** package installed |
| Onboarding | Tenant routes `/kyc` — upload ID → verify or queue |
| Queue | Dispatch inside tenant request; Stancl `QueueTenancyBootstrapper` keeps `ProcessKycDocument` on the right DB |
| Review UI | Workspace Filament panel at `/{tenant}/workspace` |
| Per-tenant settings | Virtual attributes `kyc_country`, `kyc_level`, `kyc_extraction_driver` (or config defaults) |

```php
use App\Support\Kyc;

if (Kyc::ready()) {
    // onboarding + workspace review are available
}
```

### Per-tenant config

```php
$tenant->kyc_country = 'sa';
$tenant->kyc_level = 'standard';
$tenant->kyc_extraction_driver = 'fake';
$tenant->save();

$tenant->run(function () use ($user, $file) {
    app(\App\Services\TenantKycService::class)->submit($file, $user);
});
```

### Filament note

`KycFilamentPlugin` from the package targets Filament ^3. Tenant Kit runs **Filament 5**, so the workspace panel registers a compatible bridge resource (`App\Filament\Workspace\...`). If you are on Filament 3, the package plugin is used instead.

### Levels

| Level | Behavior |
|-------|----------|
| `internal` / `standard` | Extraction + country validators only (`KYC_EXTERNAL_ENABLED=false`) |
| `full` | Same as standard, then Shufti Pro via [`kyc-ai/external-shufti`](https://packagist.org/packages/kyc-ai/external-shufti) |

Default to **`standard`** for local/demo. Use **`full`** when you have Shufti credentials and accept outbound document transfer.

### External verification (Shufti)

```bash
composer require kyc-ai/laravel:^1.1.1 kyc-ai/external-shufti
php artisan vendor:publish --tag=kyc-shufti-config
```

```env
KYC_LEVEL=full
KYC_EXTERNAL_ENABLED=true
KYC_EXTERNAL_DRIVER=shufti

SHUFTI_CLIENT_ID=
SHUFTI_SECRET=
SHUFTI_BASE_URL=https://api.shuftipro.com
SHUFTI_CALLBACK_URL="${APP_URL}/kyc/webhooks/shufti"
SHUFTI_POLL_SECONDS=30
SHUFTI_POLL_INTERVAL=2
SHUFTI_WEBHOOK_ROUTE=true
```

Flow: submit → poll (or webhook `POST /kyc/webhooks/shufti`) → `ShuftiVerificationCompleted`. Docs: [kyc-external-shufti](https://github.com/mohammedelkarsh/kyc-external-shufti).

## Test scenarios

With the package installed locally:

```bash
composer require kyc-ai/laravel:^1.1.1
php artisan test --filter=Kyc
```

| Scenario | Expected |
|----------|----------|
| Flag off (default) | `Kyc::ready()` false; no workspace Filament panel |
| Package missing | `KycFeatureTest` skipped; prep tests still pass |
| Owner opens `/kyc` | Onboarding form (country/level) |
| Guest opens `/kyc` | Redirect to login |
| `KYC_ENABLED=false` + package | `/kyc` → 404 |
| Sync upload | Result page + `kyc_verifications` row in tenant DB |
| Queue checkbox | `ProcessKycDocument` dispatched |
| Per-tenant settings | `kyc_country` / `kyc_level` / `kyc_extraction_driver` applied via `TenantKycService` |
| `GET /api/kyc/status` | Sanctum `kyc:read` — readiness + latest verification (`plan_allows` when gating on) |
| `POST /api/kyc/verifications/{uuid}/retry` | Sanctum `kyc:retry` — national_id-only re-queue (document not retained) |
| Plan gating | With `PLAN_GATING_ENABLED=true`, KYC needs plan feature `kyc` (Pro by default) — [plan-gating.md](plan-gating.md) |

> Packagist `1.1.0` may need `symfony/process` ^8 for Laravel 13. If install fails, use a path/VCS install of [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) that allows `symfony/process` `^6.4\|^7.0\|^8.0`.

## Related

- Package guide: [tenant-kit.md](https://github.com/mohammedelkarsh/laravel-kyc-ai/blob/main/docs/tenant-kit.md)
- [ROADMAP](ROADMAP.md) — v1.4.0 Phase A / B
