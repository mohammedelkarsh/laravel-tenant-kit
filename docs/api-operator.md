# api-operator integration

Operate Tenant Kit from the command line or an HTTP agent — no Filament clicks required.

[api-operator](https://github.com/mohammedelkarsh/api-operator) is a **separate Python package** ([PyPI](https://pypi.org/project/api-operator/)). Tenant Kit stays PHP-only; you install the agent alongside your app.

## Install

```bash
pip install "api-operator>=1.1.0"
```

Optional OpenAI planner / RAG extras:

```bash
pip install "api-operator[llm]>=1.1.0"
pip install "api-operator[rag]>=1.1.0"   # embeddings + pgvector client
```

Requires **Python 3.11+**.

## Prerequisites

1. Tenant Kit running (`php artisan migrate --seed`)
2. Hosts entries for central + demo subdomain (see [docker.md](docker.md))
3. A Sanctum API token (see below)

## Adapter in this repo

```text
integrations/api-operator/
├── adapter.yaml   # HTTP tools for central + tenant API
├── policy.yaml    # Safe API Agent rules (api-operator >= 0.11)
├── knowledge/     # Optional keyword/embedding docs for the agent (1.1+)
└── README.md
```

Keep in sync with [api-operator/examples/tenant-kit-adapter/](https://github.com/mohammedelkarsh/api-operator/tree/main/examples/tenant-kit-adapter).

### Tools

| Tool | API | Ability |
|------|-----|---------|
| `list_workspaces` | `GET /api/workspaces` | `workspaces:read` |
| `create_workspace` | `POST /api/workspaces` | `workspaces:write` |
| `get_workspace` | `GET /api/workspaces/{id}` | `workspaces:read` |
| `get_subscription` | `GET /api/workspaces/{id}/subscription` | `workspaces:read` |
| `get_usage` | `GET /api/workspaces/{id}/usage` | `workspaces:read` |
| `invite_team_member` | `POST /api/team/invitations` (tenant host) | `team:invite` |

Tools marked `dangerous: true` in the adapter require user confirmation in the agent.

## Issue API tokens

### Central token (workspaces, subscription, usage)

```bash
curl -X POST http://laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@laravel-tenant-kit.test",
    "password": "password",
    "device_name": "api-operator"
  }'
```

Save the `token` value. Optionally set in `.env`:

```env
TENANT_KIT_API_TOKEN=your-token-here
```

### Tenant token (team invites)

Team tools run on the **workspace subdomain** and need a tenant-scoped token:

```bash
curl -X POST http://demo.laravel-tenant-kit.test/api/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@demo.test",
    "password": "password",
    "device_name": "api-operator",
    "abilities": ["team:read", "team:invite"]
  }'
```

Use seeded credentials from the main [README](../README.md) in local dev only.

## CLI quick start

From the tenant-kit repo root:

```bash
export TENANT_KIT_API_TOKEN="your-central-token"

api-operator chat \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml \
  --base-url http://laravel-tenant-kit.test
```

Docker (port **8080**):

```bash
api-operator chat \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml \
  --base-url http://laravel-tenant-kit.test:8080 \
  --token "YOUR_TOKEN"
```

List tools:

```bash
api-operator tools \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml
```

Example prompts:

- `list workspaces`
- `get usage for demo`
- `get subscription for demo`
- `invite new@example.test to demo as member` (requires tenant token configured for tenant host)
- `get kyc status for demo` (tenant token with `kyc:read`)
- `retry kyc verification <uuid> for demo` (tenant token with `kyc:retry`; confirms first)

## HTTP server

Run the agent as a local API:

```bash
api-operator serve --port 8100
```

```http
POST http://127.0.0.1:8100/v1/chat
Content-Type: application/json

{
  "adapter": "yaml",
  "config_path": "integrations/api-operator/adapter.yaml",
  "adapter_config": {
    "token": "YOUR_CENTRAL_TOKEN",
    "base_url": "http://laravel-tenant-kit.test:8080"
  },
  "message": "list workspaces",
  "abilities": ["workspaces:read"]
}
```

Paths are relative to where you start `api-operator serve`.

## In-app chat widget (Tenant Kit UI)

When logged in on the **central** domain, a floating chat button appears (bottom-right). It proxies messages to `api-operator serve` — tokens stay server-side.

### Enable locally (Laragon / host)

1. Install and start the operator:

```bash
pip install "api-operator>=1.1.0"
```

On **Windows PowerShell**, if `api-operator` is not recognized (Scripts folder not on PATH), use:

```powershell
python -m api_operator.server.cli serve --host 127.0.0.1 --port 8100 --adapter yaml --planner mock
```

On Linux/macOS (or when the script is on PATH):

```bash
api-operator serve --host 127.0.0.1 --port 8100 --adapter yaml --planner mock
```

Optional knowledge (keyword L0 — set on the **Python** process, not Laravel). Run from the **tenant-kit repo root** so relative paths resolve:

```powershell
$env:API_OPERATOR_KNOWLEDGE_ENABLED = "true"
$env:API_OPERATOR_KNOWLEDGE_BACKEND = "keyword"
$env:API_OPERATOR_KNOWLEDGE_PATHS = "integrations/api-operator/knowledge/shared.md"
python -m api_operator.server.cli serve --host 127.0.0.1 --port 8100 --adapter yaml --planner mock
```

2. In Tenant Kit `.env`:

```env
API_OPERATOR_ENABLED=true
API_OPERATOR_URL=http://127.0.0.1:8100
API_OPERATOR_ADAPTER_PATH=integrations/api-operator/adapter.yaml
# optional override; sibling policy.yaml loads automatically with api-operator >= 0.11
# API_OPERATOR_POLICY_PATH=integrations/api-operator/policy.yaml
```

Run `api-operator serve` from the **tenant-kit repo root** so the adapter path resolves.

3. Log in as platform admin → open any central page → click the chat button.

### Policy, dry-run, and deny

- `integrations/api-operator/policy.yaml` — confirm dangerous tools, session caps, viewer deny rules
- Proxy accepts optional `dry_run: true` (no upstream side effects; no `agent_calls` meter)
- Operator may return `status: deny` for policy/quota/ability failures — surface the message as-is
- **In-app widget (Filament/admin):** create-workspace and invite flows call the proxy with `dry_run: true` first, show the preview, then **Apply change** runs the real tool call

CLI dry-run:

```bash
api-operator chat \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml \
  --dry-run
```

### Usage metering (`agent_calls`)

Only **completed tool calls** increment the workspace **`agent_calls`** meter (`status=ok` and a `tool` name). Dry-run, confirm prompts, and deny responses do not count.

Attribution (first match wins):

1. Optional JSON body field `workspace_id` (also forwarded as `tenant_id` to the operator)
2. Else `API_OPERATOR_BILLING_WORKSPACE` in `.env` (Docker defaults to `demo`)
3. Else no record is written

Failed proxy calls (502/503) and disabled usage billing do not increment the meter. See billing UI `/billing/{tenant}` and `GET /api/workspaces/{id}/usage`.

### Enable with Docker

```bash
docker compose --profile operator up -d
docker compose exec app php artisan migrate --seed
npm run build   # or: docker compose --profile build run --rm node
```

Copy `.env.docker` to `.env` (includes `API_OPERATOR_*` and `connect_host=nginx`).

### Example chat flow

```
you>  list workspaces
agent> list_workspaces succeeded: …
you>  create workspace Acme subdomain acme
agent> Confirm create_workspace? Reply yes…
you>  yes
agent> create_workspace succeeded
```

Remember to add `127.0.0.1 acme.laravel-tenant-kit.test` to hosts before opening the new workspace in a browser.

## End-to-end integration test

With Tenant Kit running, from a clone of [api-operator](https://github.com/mohammedelkarsh/api-operator):

```bash
pip install -e ".[dev]"
python scripts/integration_tenant_kit.py
```

Docker:

```bash
python scripts/integration_tenant_kit.py --base-url http://laravel-tenant-kit.test:8080
```

Expect `INTEGRATION PASSED`.

Optional pytest (requires env vars):

```bash
export TENANT_KIT_BASE_URL=http://laravel-tenant-kit.test:8080
export TENANT_KIT_API_TOKEN=your-token
pytest -m integration -q
```

## Docker workflow

1. `docker compose --profile operator up -d` from tenant-kit root
2. `docker compose exec app php artisan migrate --seed`
3. Log in on the central domain and use the in-app chat widget, **or** issue a token and use CLI

See [docker.md](docker.md) for full stack details.

## Related docs

- [API reference](api.md) — Sanctum endpoints and abilities
- [ROADMAP](ROADMAP.md) — v1.3.1 scope and future api-operator work
