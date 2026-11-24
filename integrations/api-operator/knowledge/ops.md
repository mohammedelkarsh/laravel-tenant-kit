# Operations knowledge for api-operator

## Production database

Prefer PostgreSQL for production-shaped deploys (`docs/production.md`, Docker profile `pgsql`, `.env.docker.pgsql`). MySQL remains fine for Laragon demos.

## Docker profiles

- default: MySQL + Redis + app
- `pgsql`: PostgreSQL instead of (or beside) MySQL service
- `operator`: api-operator sidecar on port 8100
- `build`: npm asset build

## Knowledge / RAG

Enable on the Python sidecar:

```
API_OPERATOR_KNOWLEDGE_ENABLED=true
API_OPERATOR_KNOWLEDGE_BACKEND=keyword
API_OPERATOR_KNOWLEDGE_PATHS=integrations/api-operator/knowledge
```

A directory path loads all `*.md` / `*.txt` recursively (api-operator 1.1.1+).

## Safety

Confirm dangerous tools; prefer dry-run for writes; never invent Sanctum tokens.
