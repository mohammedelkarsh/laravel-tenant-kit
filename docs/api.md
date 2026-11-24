# API Reference

Token-based API using [Laravel Sanctum](https://laravel.com/docs/sanctum).

## Rate limiting

`POST /api/auth/token` is limited to **5 requests per minute per IP** by default.

```env
API_AUTH_RATE_LIMIT=5
API_AUTH_RATE_DECAY=1
```

## Token abilities

When issuing a token you may pass an optional `abilities` array. If omitted, all abilities for that context are granted.

**Central abilities:** `user:read`, `workspaces:read`, `workspaces:write`

**Tenant abilities:** `user:read`, `team:read`, `team:invite`, `kyc:read`, `kyc:retry`

```bash
curl -X POST http://laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@laravel-tenant-kit.test",
    "password": "password",
    "device_name": "cli",
    "abilities": ["workspaces:read", "user:read"]
  }'
```

---

## Central API

Base URL: `http://{CENTRAL_DOMAIN}/api`

### Obtain a token

```bash
curl -X POST http://laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@laravel-tenant-kit.test","password":"password","device_name":"cli"}'
```

Response:

```json
{
  "token": "1|...",
  "abilities": ["user:read", "workspaces:read", "workspaces:write"],
  "user": { "id": 1, "name": "Platform Admin", "email": "admin@laravel-tenant-kit.test" }
}
```

### Authenticated requests

```bash
curl http://laravel-tenant-kit.test/api/workspaces \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Endpoints

| Method | Path | Ability | Description |
|--------|------|---------|-------------|
| POST | `/api/auth/token` | — | Issue API token (rate limited) |
| DELETE | `/api/auth/token` | — | Revoke current token |
| GET | `/api/user` | `user:read` | Current user |
| GET | `/api/workspaces` | `workspaces:read` | List all workspaces |
| POST | `/api/workspaces` | `workspaces:write` | Create workspace (`name`, `subdomain`) |
| GET | `/api/workspaces/{id}` | `workspaces:read` | Workspace details |
| GET | `/api/workspaces/{id}/subscription` | `workspaces:read` | Stripe subscription status + usage summary |
| GET | `/api/workspaces/{id}/usage` | `workspaces:read` | Current billing period usage meters |

### Subscription response example

```json
{
  "data": {
    "workspace_id": "demo",
    "subscribed": false,
    "status": null,
    "plan": null,
    "plan_name": null,
    "stripe_price": null,
    "on_trial": false,
    "cancelled": false,
    "ends_at": null,
    "trial_ends_at": null,
    "usage": {
      "period_start": "2026-06-01",
      "period_end": "2026-06-30",
      "meters": {
        "api_calls": {
          "label": "API calls",
          "description": "Authenticated API requests (central + tenant).",
          "quantity": 12,
          "event_name": "api_calls"
        },
        "team_seats": {
          "label": "Team seats",
          "description": "Active members in the workspace.",
          "quantity": 3,
          "event_name": "team_seats"
        }
      }
    }
  }
}
```

### Usage response example

```json
{
  "data": {
    "workspace_id": "demo",
    "period_start": "2026-06-01",
    "period_end": "2026-06-30",
    "meters": {
      "api_calls": {
        "label": "API calls",
        "quantity": 12,
        "event_name": "api_calls"
      },
      "team_seats": {
        "label": "Team seats",
        "quantity": 3,
        "event_name": "team_seats"
      }
    }
  }
}
```

Configure meters in `config/usage.php`. Set `USAGE_SYNC_TO_STRIPE=true` to forward events to Stripe Billing Meters via Cashier.

---

## Tenant API

Base URL: `http://{workspace}.{CENTRAL_DOMAIN}/api`

Tenancy is resolved from the subdomain automatically.

Suspended workspaces return **403** with a JSON error message.

### Obtain a token

```bash
curl -X POST http://demo.laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@demo.test","password":"password","device_name":"mobile"}'
```

### Endpoints

| Method | Path | Ability | Description |
|--------|------|---------|-------------|
| POST | `/api/auth/token` | — | Issue tenant API token (rate limited) |
| DELETE | `/api/auth/token` | — | Revoke current token |
| GET | `/api/user` | `user:read` | Current user + tenant context |
| GET | `/api/team` | `team:read` | Team members |
| POST | `/api/team/invitations` | `team:invite` | Invite member (`email`, `role`: `admin` or `member`) |
| GET | `/api/kyc/status` | `kyc:read` | KYC readiness + latest verification (no secrets) |
| POST | `/api/kyc/verifications/{uuid}/retry` | `kyc:retry` | Re-queue failed/pending verification (national_id only) |

### Invite example

```bash
curl -X POST http://demo.laravel-tenant-kit.test/api/team/invitations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"email":"new@example.test","role":"member"}'
```

---

## OAuth (web)

Social login is available on the central login page when configured:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://laravel-tenant-kit.test/auth/google/callback

GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=http://laravel-tenant-kit.test/auth/github/callback
```

Routes: `GET /auth/{provider}/redirect` and `GET /auth/{provider}/callback`  
Providers: `google`, `github`

---

## AI operator

Operate these APIs from the terminal or the **in-app guided agent** (central domain, when `API_OPERATOR_ENABLED=true`):

- Web routes: `GET /api-operator/status`, `POST /api-operator/chat` (authenticated, CSRF)
- Full guide: [docs/api-operator.md](api-operator.md)
