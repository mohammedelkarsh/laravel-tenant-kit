# Platform outbound webhooks (v1.6)

Opt-in HTTP callbacks when important platform events happen. Disabled by default.

## Enable

```env
PLATFORM_WEBHOOKS_ENABLED=true
PLATFORM_WEBHOOKS_URL=https://example.com/hooks/tenant-kit
PLATFORM_WEBHOOKS_SECRET=generate-a-long-random-string
PLATFORM_WEBHOOKS_TIMEOUT=5
PLATFORM_WEBHOOKS_QUEUE=true
```

Use a queue worker in production (`PLATFORM_WEBHOOKS_QUEUE=true`, the default). Set `PLATFORM_WEBHOOKS_QUEUE=false` only for local demos.

## Events

| Event | When |
|-------|------|
| `workspace.created` | After `TenantProvisioner` finishes (domain + optional owner) |
| `workspace.suspended` | `suspended_at` set on the workspace |
| `workspace.unsuspended` | `suspended_at` cleared |
| `workspace.invite_sent` | Team invite created (web UI or tenant API) |
| `kyc.verification.passed` | KYC pipeline approved, or Filament review approved |
| `kyc.verification.failed` | KYC pipeline failed, or Filament review rejected |
| `kyc.verification.pending_review` | KYC pipeline recommends manual review |

KYC events require `kyc-ai/laravel` and fire only while platform webhooks are enabled. Payloads **omit** `national_id` and document paths.

## Envelope

```json
{
  "id": "delivery-uuid",
  "event": "workspace.created",
  "created_at": "2026-11-24T10:00:00+00:00",
  "data": {
    "workspace_id": "demo",
    "name": "Demo",
    "url": "http://demo.laravel-tenant-kit.test"
  }
}
```

## Headers

| Header | Value |
|--------|--------|
| `Content-Type` | `application/json` |
| `X-Tenant-Kit-Event` | Event name |
| `X-Tenant-Kit-Delivery` | Same as envelope `id` |
| `X-Tenant-Kit-Signature` | `sha256=` + HMAC-SHA256 of **raw body** using `PLATFORM_WEBHOOKS_SECRET` (omitted if secret empty) |

### Verify signature (PHP)

```php
$signature = $_SERVER['HTTP_X_TENANT_KIT_SIGNATURE'] ?? '';
$expected = 'sha256='.hash_hmac('sha256', $rawBody, env('PLATFORM_WEBHOOKS_SECRET'));
hash_equals($expected, $signature);
```

## Retries

`DeliverPlatformWebhook` is queued with **3 tries**. Failures are logged (`webhooks.delivery_failed` / `webhooks.delivery_error`).

## Usage metering

Successful deliveries increment the `webhook_deliveries` meter for the tenant matching `data.workspace_id` (when present). Failed attempts and payloads without a workspace id are not metered. Stripe event name: `STRIPE_METER_WEBHOOK_DELIVERIES` (default `webhook_deliveries`).

## Not in scope (yet)

- Per-tenant webhook endpoints (customer-configured)
