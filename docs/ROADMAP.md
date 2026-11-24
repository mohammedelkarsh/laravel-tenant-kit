# Development roadmap

Planned releases from the current stable tag through **v1.7+**. Dates are not committed until scoped.

## Overview

| Version | Theme | Repo | Status |
|---------|--------|------|--------|
| **v1.2.3** | CI / smoke-test hardening | tenant-kit | ✅ Released |
| **v1.3.0** | Usage-based billing | tenant-kit | ✅ Released |
| **v1.3.1** | [api-operator](https://pypi.org/project/api-operator/) (PyPI) + in-app guided agent | tenant-kit + [api-operator](https://github.com/mohammedelkarsh/api-operator) | ✅ Released |
| **v1.4.0** | Optional KYC module | tenant-kit + [laravel-kyc-ai](https://github.com/mohammedelkarsh/laravel-kyc-ai) | ✅ Released |
| **v1.5.0** | `agent_calls` usage meter + Stripe | tenant-kit | ✅ Released |
| **v1.5.3** | api-operator 0.11 Safe API Agent (policy / dry-run / metering) | tenant-kit + [api-operator](https://github.com/mohammedelkarsh/api-operator) | ✅ Released |
| **v1.5.4** | api-operator 1.1 knowledge docs (keyword/embedding/pgvector) | tenant-kit + api-operator | ✅ Released |
| **v1.5.5** | KYC Sanctum status + retry APIs for api-operator | tenant-kit + api-operator | ✅ Released |
| **v1.5.6** | `outbound_emails` usage meter | tenant-kit | ✅ Released |
| **v1.5.7** | `storage_bytes` usage meter | tenant-kit | ✅ Released |
| **v1.5.x** | Remaining usage meters (webhooks) | tenant-kit | 💡 Planned |
| **v1.6.0** | Platform webhooks (+ plan gating / smarter agent follow-ups) | tenant-kit + api-operator | ✅ Webhooks shipped; follow-ups open |
| **v1.7+** | Enterprise (SSO, audit, export) | tenant-kit | 🔭 Under consideration |
| **v2.0** | Breaking changes | — | Not planned yet |

**Semver:** patch (1.2.x) = fixes · minor (1.3.x–1.7) = new features · major (2.0) = breaking changes.

**Contributors welcome:** ~~[#1 French locale](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/1)~~ ✅ · ~~[#6 Spanish locale](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/6)~~ ✅ · ~~[#2 Laragon docs](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/2)~~ ✅ · ~~[#3 v1.4 KYC prep](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/3)~~ ✅

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
| PyPI `api-operator==0.10.0` | ✅ (superseded by 0.11.0 — see v1.5.3) |

The Python package lives in a **separate repo**: [api-operator](https://github.com/mohammedelkarsh/api-operator). Tenant Kit stays PHP-only; the operator runs as a sidecar.

**Still out of scope for v1.3.1:** embedding Python in PHP, AI usage meters (see v1.5.0).

---

## v1.5.3 — api-operator Safe API Agent ✅

**Goal:** Opt-in upgrade path for [api-operator](https://pypi.org/project/api-operator/) **0.11+** (policy, dry-run, audit/quota hooks) without breaking installs that leave the agent disabled.

| Item | Status |
|------|--------|
| `integrations/api-operator/policy.yaml` | ✅ |
| Proxy `dry_run` / `actor_id` / `tenant_id` | ✅ |
| `agent_calls` only on completed tools | ✅ |
| Docs + `.env.example` (`>=0.11`) | ✅ |
| Widget styling for `status=deny` | ✅ |
| PyPI `api-operator==0.11.0` | ✅ |

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

### Stretch (moved to v1.6)

| Item | Target | Notes |
|------|--------|--------|
| KYC webhooks | v1.6.0 | Notify workspace when verification status changes |
| Plan gating (KYC) | v1.6.0 | KYC enabled per Stripe subscription tier — part of general plan gating |

---

## v1.5.0 — `agent_calls` usage meter ✅

**Goal:** Bill and monitor guided agent chat separately from API calls.

| Item | Notes |
|------|--------|
| `agent_calls` meter | ✅ Successful `POST /api-operator/chat` → `UsageMeter::record` |
| Attribution | ✅ Request `workspace_id` or `API_OPERATOR_BILLING_WORKSPACE` |
| Stripe sync | ✅ Same `USAGE_SYNC_TO_STRIPE` + `STRIPE_METER_AGENT_CALLS` |
| Billing UI / usage API | ✅ Auto via `config/usage.php` meters list |
| Tests | ✅ `ApiOperatorChatTest` + `UsageBillingTest` |

**Release checklist:** ✅ — [v1.5.0](https://github.com/mohammedelkarsh/laravel-tenant-kit/releases/tag/v1.5.0)

---

## v1.5.6 — `outbound_emails` usage meter ✅

**Goal:** Start remaining usage meters deferred from v1.5.0 / v1.5.1.

| Item | Notes |
|------|--------|
| Outbound email meter | ✅ `MessageSent` while tenancy initialized → `outbound_emails` |
| Stripe sync | ✅ `STRIPE_METER_OUTBOUND_EMAILS` via existing `USAGE_SYNC_TO_STRIPE` |
| Tests | ✅ `UsageBillingTest` |

**Release:** [v1.5.6](https://github.com/mohammedelkarsh/laravel-tenant-kit/releases/tag/v1.5.6)

## v1.5.7 — `storage_bytes` usage meter ✅

| Item | Notes |
|------|--------|
| Storage meter | ✅ Gauge bytes under tenant `storage/app` (local disk) |
| Snapshot | ✅ On usage/billing API + `php artisan usage:snapshot-gauges` (daily) |
| Stripe sync | ✅ `STRIPE_METER_STORAGE_BYTES` |

**Release:** [v1.5.7](https://github.com/mohammedelkarsh/laravel-tenant-kit/releases/tag/v1.5.7)

## Remaining usage meters 💡

| Item | Notes |
|------|--------|
| Webhooks usage meter | Count outbound webhook deliveries (usage billing — not platform webhooks; needs platform webhook sender first → v1.6) |

Ship when ready (patch).

---

## v1.6.0 — Platform + smarter agent 💡

**Goal:** Integrations, subscription-tier features, and agent UX for power users.

| Item | Notes |
|------|--------|
| Outbound platform webhooks | ✅ Opt-in signed POSTs — [docs/webhooks.md](webhooks.md) |
| Plan gating | ✅ Opt-in — [docs/plan-gating.md](plan-gating.md) (v1.6.1) |
| KYC webhooks | ✅ Via platform webhooks — `kyc.verification.*` (v1.6.2) |
| api-operator RAG | ✅ Curated knowledge folder + Docker wiring (v1.6.4; api-operator 1.1.1 dirs) |
| Filament agent UX | ✅ Dry-run preview before create/invite writes (v1.6.3) |
| PostgreSQL-first docs | ✅ [production.md](production.md) + `.env.docker.pgsql` (v1.6.3) |

---

## v1.7+ — Enterprise 🔭

Under consideration (not scoped):

- SSO / SAML on central app
- Platform audit log (admin actions)
- Tenant export / backup CLI
- Multi-region tenancy notes

---

## Backlog & ecosystem (parallel)

| Item | Target | Repo | Notes |
|------|--------|------|--------|
| French locale | ✅ Shipped | tenant-kit | [#1](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/1) |
| Spanish locale | ✅ Shipped | tenant-kit | [#6](https://github.com/mohammedelkarsh/laravel-tenant-kit/issues/6) |
| Shufti external KYC driver | ✅ Packagist `kyc-ai/external-shufti` 1.0.0 | [kyc-external-shufti](https://github.com/mohammedelkarsh/kyc-external-shufti) | Full level documented in [docs/kyc.md](kyc.md) |
| Other external verifiers (Onfido, …) | Later | satellite packages | Opt-in; not in tenant-kit core |
| Government/registry APIs (e.g. Yakeen) | Later | country-specific packages | Separate from core KYC |

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
