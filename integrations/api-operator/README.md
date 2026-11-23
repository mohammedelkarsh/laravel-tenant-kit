# api-operator integration

Connect [api-operator](https://github.com/mohammedelkarsh/api-operator) to this app via **YAML adapter** — no Python in this repo.

- **PyPI:** [pypi.org/project/api-operator](https://pypi.org/project/api-operator/)
- **Full guide:** [docs/api-operator.md](../../docs/api-operator.md)

## In-app guided agent

When `API_OPERATOR_ENABLED=true` and the operator is running, logged-in users on the **central** domain see a chat button (dashboard, Filament, landing). Tokens stay server-side via Laravel proxy routes.

## Prerequisites

1. Tenant Kit running (match `APP_URL` in `.env`)
2. `php artisan migrate --seed`
3. `pip install "api-operator>=1.1.0"`

## Quick start

```bash
# Issue a central token (see docs/api-operator.md)
export TENANT_KIT_API_TOKEN="your-sanctum-token"

api-operator chat \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml \
  --base-url http://laravel-tenant-kit.test
```

Docker on port 8080:

```bash
api-operator chat \
  --adapter yaml \
  --config integrations/api-operator/adapter.yaml \
  --base-url http://laravel-tenant-kit.test:8080 \
  --token "YOUR_TOKEN"
```

## Integration test

From an [api-operator](https://github.com/mohammedelkarsh/api-operator) clone:

```bash
python scripts/integration_tenant_kit.py --base-url http://laravel-tenant-kit.test:8080
```

## Files

| File | Purpose |
|------|---------|
| `adapter.yaml` | HTTP tools for Sanctum API |
| `policy.yaml` | Safe API Agent rules (confirm/deny/caps) |
| `knowledge/` | Optional docs for api-operator knowledge (1.1+) |
| `README.md` | This guide |

Keep in sync with `api-operator/examples/tenant-kit-adapter/` when upgrading either repo.

### Knowledge (optional, api-operator 1.1+)

Knowledge is configured on the **sidecar** process:

```bash
export API_OPERATOR_KNOWLEDGE_ENABLED=true
export API_OPERATOR_KNOWLEDGE_BACKEND=keyword
export API_OPERATOR_KNOWLEDGE_PATHS=integrations/api-operator/knowledge/shared.md
```

See [api-operator docs/knowledge.md](https://github.com/mohammedelkarsh/api-operator/blob/main/docs/knowledge.md).
