# KYC knowledge for api-operator

Opt-in identity verification via kyc-ai/laravel (`KYC_ENABLED=true`).

## Tools

- `get_kyc_status` (tenant host, ability `kyc:read`) — readiness flags + latest verification (no secrets).
- `retry_kyc_verification` (ability `kyc:retry`, confirm) — re-queue by uuid using stored national_id only.

## Plan gating

When `PLAN_GATING_ENABLED=true`, KYC needs plan feature `kyc` (Pro by default). Status JSON includes `plan_allows`.

## Webhooks

With platform webhooks enabled: `kyc.verification.passed`, `kyc.verification.failed`, `kyc.verification.pending_review`. Payloads omit national_id.

## Operator guidance

- Prefer status before retry; never invent tokens.
- Full document re-upload happens on tenant `/kyc`, not via the agent.
