# Tenant Kit operator knowledge (shared)

Use these notes when helping operators via api-operator.

## Workspaces

- List workspaces with the `list_workspaces` tool.
- Create a workspace with `create_workspace` (name + subdomain). Creation is dangerous and needs confirmation.
- Fetch details with `get_workspace` using an id or subdomain.

## Billing

- `get_subscription` returns Stripe subscription status for a workspace.
- `get_usage` returns meters for the current billing period (`api_calls`, `agent_calls`, seats, etc.).

## Team

- `invite_team_member` runs on the tenant host (`host: tenant`) and needs a tenant-scoped token with `team:invite`.
- Prefer role `member` unless the user asks for admin.

## KYC

- `get_kyc_status` runs on the tenant host and needs ability `kyc:read`.
- It returns whether KYC is ready and the latest verification (if any). No secrets are exposed.
- `retry_kyc_verification` needs ability `kyc:retry`, confirmation, and a verification `uuid` from status.
- Retry re-queues using the stored national_id only (original document is not retained). Passed verifications cannot be retried; otherwise re-upload via `/kyc`.

## Safety

- Prefer dry-run when the user wants to preview an action.
- Policy may deny viewer roles from create/invite tools.
- Never invent API tokens; the Laravel proxy injects them server-side.
