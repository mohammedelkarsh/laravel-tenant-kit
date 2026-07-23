# KYC (optional)

Identity verification for workspaces — **opt-in**, not required to run Tenant Kit.

## Status

**Phase A (prep):** feature flag + this stub. Nothing is registered when `KYC_ENABLED=false` (default).

**Phase B (planned):** integrate [kyc-ai/laravel](https://packagist.org/packages/kyc-ai/laravel) / [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) as an optional dependency — tenant migrations, Filament review plugin, queue, onboarding example.

See [ROADMAP](ROADMAP.md) — v1.4.0.

## Enable the flag (prep only)

```env
KYC_ENABLED=false
```

Set to `true` only after Phase B wiring lands; with the flag alone, no KYC UI or routes appear yet.

```php
use App\Support\Kyc;

if (Kyc::enabled()) {
    // Phase B will register routes / panels behind this check
}
```

## Install path (Phase B — not yet)

```bash
composer require kyc-ai/laravel
```

Do **not** add this to the default `composer.json` `require` of Tenant Kit. Follow the package’s tenant-kit guide when published.

## Related

- [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai)
- [ROADMAP](ROADMAP.md) — v1.4.0 Phase A / B
