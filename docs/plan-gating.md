# Plan feature gating (v1.6)

Opt-in: restrict workspace capabilities by Stripe subscription plan. **Off by default** so local demos work without Stripe.

## Enable

```env
PLAN_GATING_ENABLED=true
PLAN_GATING_DEFAULT_PLAN=free
```

Plans and feature lists live in `config/plans.php`.

| Plan | Default features |
|------|------------------|
| `free` | `teams`, `api` |
| `starter` | `teams`, `api`, `agent` |
| `pro` | `teams`, `api`, `agent`, `kyc` |

Customize by editing `features` on each plan. Unknown Stripe prices fall back to `PLAN_GATING_DEFAULT_PLAN`.

## Behaviour

- When **disabled**: all features are allowed (existing `KYC_ENABLED` / package checks still apply).
- When **enabled** and the workspace is not subscribed: features of the default plan apply.
- `Kyc::ready()` requires feature `kyc` **only while tenancy is initialized** (so Filament panel registration still works).
- Subscription API payload includes `features` and `plan_gating`.
- `GET /api/kyc/status` includes `plan_allows`.

## Example

```php
use App\Support\PlanFeatures;

PlanFeatures::allows(tenant(), 'kyc'); // true on Pro when gating is on
```
