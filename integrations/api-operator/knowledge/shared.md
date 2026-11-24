# Tenant Kit operator knowledge (shared)

Use these notes when helping operators via api-operator.
More detail: sibling files `billing.md`, `kyc.md`, `webhooks.md`, `ops.md` in this folder.

## Workspaces

- List workspaces with the `list_workspaces` tool.
- Create a workspace with `create_workspace` (name + subdomain). Creation is dangerous and needs confirmation / dry-run preview in the UI.
- Fetch details with `get_workspace` using an id or subdomain.

## Billing

- `get_subscription` returns Stripe subscription status for a workspace.
- `get_usage` returns meters for the current billing period (`api_calls`, `agent_calls`, `outbound_emails`, `storage_bytes`, `webhook_deliveries`, seats, etc.).

## Team

- `invite_team_member` runs on the tenant host (`host: tenant`) and needs a tenant-scoped token with `team:invite`.
- Prefer role `member` unless the user asks for admin.

## KYC

- `get_kyc_status` / `retry_kyc_verification` — see `kyc.md`.

## Safety

- Prefer dry-run when the user wants to preview an action.
- Policy may deny viewer roles from create/invite tools.
- Never invent API tokens; the Laravel proxy injects them server-side.
