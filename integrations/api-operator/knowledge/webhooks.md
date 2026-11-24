# Platform webhooks knowledge for api-operator

Opt-in signed HTTP callbacks (`PLATFORM_WEBHOOKS_ENABLED`).

## Events

- `workspace.created` — after provision
- `workspace.suspended` / `workspace.unsuspended`
- `workspace.invite_sent`
- `kyc.verification.passed` / `failed` / `pending_review`

## Delivery

Queued job `DeliverPlatformWebhook`, HMAC header `X-Tenant-Kit-Signature` (`sha256=` + body).
Successful deliveries with `workspace_id` increment the `webhook_deliveries` usage meter.

## Operator guidance

Webhooks are platform configuration, not agent tools. Point operators to `docs/webhooks.md` for URL/secret setup.
