# API Reference

Token-based API using [Laravel Sanctum](https://laravel.com/docs/sanctum).

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

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/token` | Issue API token |
| DELETE | `/api/auth/token` | Revoke current token |
| GET | `/api/user` | Current user |
| GET | `/api/workspaces` | List all workspaces |
| POST | `/api/workspaces` | Create workspace (`name`, `subdomain`) |
| GET | `/api/workspaces/{id}` | Workspace details |

---

## Tenant API

Base URL: `http://{workspace}.{CENTRAL_DOMAIN}/api`

Tenancy is resolved from the subdomain automatically.

### Obtain a token

```bash
curl -X POST http://demo.laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@demo.test","password":"password","device_name":"mobile"}'
```

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/token` | Issue tenant API token |
| DELETE | `/api/auth/token` | Revoke current token |
| GET | `/api/user` | Current user + tenant context |
| GET | `/api/team` | Team members |

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
