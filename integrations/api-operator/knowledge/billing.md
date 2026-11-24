# Billing and usage knowledge for api-operator

## Tools

- `get_subscription` — Stripe status + plan for a workspace id/subdomain.
- `get_usage` — meters for the current calendar month.

## Meters

Configured in `config/usage.php`:

- `api_calls` — authenticated API hits
- `team_seats` — gauge of members
- `agent_calls` — completed guided-agent tool runs (not dry-run/deny/confirm)
- `outbound_emails` — MessageSent while tenancy is active
- `storage_bytes` — gauge of tenant `storage/app` size

## Plan gating

`PLAN_GATING_ENABLED` maps Stripe prices to feature lists (`free` / `starter` / `pro`). Subscription payload includes `features` and `plan_gating`.

## Operator guidance

- Ask which workspace subdomain when unclear.
- Dry-run does not increment `agent_calls`.
