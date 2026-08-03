# Development roadmap

Planned releases from the current stable tag through **v1.7+**. Dates are not committed until scoped.

## Overview

| Version | Theme | Repo | Status |
|---------|--------|------|--------|
| **v1.2.3** | CI / smoke-test hardening | tenant-kit | ✅ Released |
| **v1.3.0** | Usage-based billing | tenant-kit | ✅ Released |
| **v1.3.1** | [api-operator](https://pypi.org/project/api-operator/) (PyPI) + in-app guided agent | tenant-kit + [api-operator](https://github.com/mohammedelkarsh/api-operator) | ✅ Released |
| **v1.4.0** | Optional KYC module | tenant-kit + [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) | ✅ Released |
| **v1.5.0** | Extended usage meters + Stripe | tenant-kit | 💡 Planned |
| **v1.6.0** | Platform webhooks + smarter agent | tenant-kit + api-operator | 💡 Planned |
| **v1.7+** | Enterprise (SSO, audit, export) | tenant-kit | 🔭 Under consideration |
| **v2.0** | Breaking changes | — | Not planned yet |

**Semver:** patch (1.2.x) = fixes · minor (1.3.x–1.7) = new features · major (2.0) = breaking changes.

**Contributors welcome:** [#1 French locale](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/1) · ~~[#2 Laragon docs](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/2)~~ ✅ · ~~[#3 v1.4 KYC prep](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/3)~~ ✅ (maintainer)

---

## v1.3.0 — Usage-based billing ✅

**Goal:** Track workspace usage locally; optional sync to Stripe Billing Meters.

| Item | Notes |
|------|--------|
| Local meters | `api_calls` (middleware), `team_seats` (gauge snapshot) |
| Database | `usage_records` table (per tenant, meter, calendar month) |
| API | `GET /api/workspaces/{id}/usage`; subscription payload includes usage |
| Billing UI | Usage section on `/billing/{tenant}` when `USAGE_BILLING_ENABLED=true` |
| Stripe sync | Optional via `USAGE_SYNC_TO_STRIPE` + Cashier `reportMeterEvent()` |
| Config | `config/usage.php`, `.env.example` vars |
| Tests | `UsageBillingTest`, system-test + page-audit coverage |
| Adapter stub | `get_usage` tool in `integrations/api-operator/adapter.yaml` |

**Release checklist:** ✅

---

## v1.3.1 — api-operator + guided agent ✅

**Goal:** Operate Tenant Kit via [api-operator](https://github.com/mohammedelkarsh/api-operator) (CLI + HTTP) and an in-app guided chat on the central domain.

| Item | Status |
|------|--------|
| `docs/api-operator.md` | ✅ |
| `integrations/api-operator/` README + adapter | ✅ |
| In-app chat widget + guided flows | ✅ |
| Laravel proxy (`/api-operator/chat`) | ✅ |
| Docker `operator` profile + setup scripts | ✅ |
| README + screenshots + demo GIF | ✅ |
| `ApiOperatorChatTest` + adapter tests | ✅ |
| Sync with api-operator `examples/tenant-kit-adapter/` | ✅ |
| PyPI `api-operator==0.10.0` | ✅ |

The Python package lives in a **separate repo**: [api-operator](https://github.com/mohammedelkarsh/api-operator). Tenant Kit stays PHP-only; the operator runs as a sidecar.

**Still out of scope for v1.3.1:** embedding Python in PHP, AI usage meters (see v1.5.0).

---

## v1.4.0 — Optional KYC module ✅

**Goal:** Integrate [kyc-ai/laravel](https://packagist.org/packages/kyc-ai/laravel) / [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) without forcing it on every installation.

### Phase A — Prep (tenant-kit only, contributors) ✅

| Item | Notes |
|------|--------|
| `config/kyc.php` | ✅ `enabled` default `false` |
| `.env.example` | ✅ `KYC_ENABLED=false` + doc link |
| `docs/kyc.md` | ✅ Opt-in install + enable guide |
| `App\Support\Kyc` | ✅ `enabled()` / `ready()` helpers |
| Tests | ✅ `KycPrepTest` — when disabled, no KYC routes/panels |

**No `composer require` in Phase A** — avoids cross-repo confusion.

### Phase B — Integration (maintainer + laravel-kyc-ai) ✅

| Item | Notes |
|------|--------|
| Opt-in dependency | ✅ `composer require kyc-ai/laravel`; `suggest` only — not in default `require` |
| Per-tenant config | ✅ `Tenant::kycSettings()` + `TenantKycService::applyTenantConfig()` |
| Migrations | ✅ `database/migrations/tenant/..._create_kyc_verifications_table.php` |
| Filament | ✅ Workspace panel `/workspace` + Filament 5 bridge (package plugin is F3) |
| Queue | ✅ Onboarding can `dispatch()`; Stancl queue bootstrapper |
| Onboarding | ✅ `/kyc` upload → verify / queue → result |
| Reference | ✅ [docs/kyc.md](kyc.md) + package [tenant-kit.md](https://github.com/mohammedelkarsh/laravel-kyc-ai/blob/main/docs/tenant-kit.md) |
| Tests | ✅ `KycFeatureTest` (skipped when package absent) |

**Prerequisite:** stable verification drivers before promoting `KycLevel::Full` in docs.

### Also in 1.4.0

| Item | Notes |
|------|--------|
| `docs/laragon.md` | Laragon vs Docker (community PR #4) |
| `renovate.json` | Automated dependency update PRs |

### Stretch (deferred)

| Item | Notes |
|------|--------|
| KYC webhooks | Notify workspace when verification status changes |
| Plan gating | KYC enabled per subscription tier |

---

## v1.5.0 — Extended usage billing 💡

**Goal:** Expand meters introduced in v1.3.0 and link agent activity.

| Item | Notes |
|------|--------|
| `agent_calls` meter | Count successful `/api-operator/chat` proxy requests |
| Additional meters | Storage, outbound email, webhook deliveries |
| Stripe sync | Optional via existing `USAGE_SYNC_TO_STRIPE` pattern |
| Billing UI | Show new meters on `/billing/{tenant}` |
| Tests | PHPUnit + usage API coverage |

---

## v1.6.0 — Platform + smarter agent 💡

**Goal:** Integrations and agent UX for power users.

| Item | Notes |
|------|--------|
| Outbound webhooks | Workspace created, suspended, invite sent |
| Plan gating | Features enabled per Stripe subscription tier |
| api-operator RAG | Answer from tenant-kit docs |
| Filament agent UX | Confirm-before-write patterns in admin |
| PostgreSQL-first docs | Docker profile as recommended production path |

---

## v1.7+ — Enterprise 🔭

Under consideration (not scoped):

- SSO / SAML on central app
- Platform audit log (admin actions)
- Tenant export / backup CLI
- Multi-region tenancy notes

---

## Related repositories

| Repo | Role |
|------|------|
| [laravel-tenant-kit](https://github.com/mohammedelkarsh/laravel-tenant-kit) | This starter — PHP, Docker, API |
| [api-operator](https://github.com/mohammedelkarsh/api-operator) | Python AI operator — PyPI, YAML adapters |
| [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) | Optional KYC package for v1.4 |
| laravel-tenant-kit-marketing | Articles, tweets — **not** in main repo |

---

## How we ship

1. **Feature branch or main** — implement + Docker tests (`php artisan test`, `system-test.php`, `page-audit.php`).
2. **CHANGELOG + this file** — update status column when tagged.
3. **README roadmap** — check off completed minors.
4. **Git tag + GitHub release** — one story per minor (Dev.to optional, from marketing repo).
