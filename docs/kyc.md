# KYC (optional)

Identity verification for workspaces — **opt-in**, not required to run Tenant Kit.

Package: [kyc-ai/laravel](https://packagist.org/packages/kyc-ai/laravel) · [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai)

See [ROADMAP](ROADMAP.md) — v1.4.0.

## Install (adopters)

```bash
composer require kyc-ai/laravel:^1.1
```

Optional country validators:

```bash
composer require validators/ae validators/eg
```

Do **not** add `kyc-ai/laravel` to Tenant Kit’s default `composer.json` `require`.

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

Prefer **`KycLevel::standard`** (and `KYC_EXTERNAL_ENABLED=false`) until external drivers such as Shufti are production-ready. Do not promote `full` in product docs yet.

## Related

- Package guide: [tenant-kit.md](https://github.com/mohammedelkarsh/laravel-kyc-ai/blob/main/docs/tenant-kit.md)
- [ROADMAP](ROADMAP.md) — v1.4.0 Phase A / B
