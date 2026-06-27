# Screenshots

Preview images for the Laravel Tenant Kit README (captured after `php artisan db:seed`).

| File | Page | URL (Docker `:8080`) |
|------|------|----------------------|
| `demo.gif` | End-to-end walkthrough | landing → signup → tenant → team → Filament → **guided agent (open + workspaces menu)** |
| `landing.png` | SaaS landing page | `http://laravel-tenant-kit.test:8080/` |
| `admin-panel.png` | Filament dashboard + SaaS analytics widgets | `http://laravel-tenant-kit.test:8080/admin` |
| `tenant-dashboard.png` | Workspace dashboard | `http://demo.laravel-tenant-kit.test:8080/dashboard` |
| `billing.png` | Stripe billing (central) | `http://laravel-tenant-kit.test:8080/billing/demo` |
| `team-management.png` | Team members & invites | `http://demo.laravel-tenant-kit.test:8080/team` |
| `api-operator-chat.png` | Guided agent (api-operator widget) | `http://laravel-tenant-kit.test:8080/dashboard` (logged in, operator running) |

Laragon (port 80): same hosts without `:8080` when using `.env.example` (not `.env.docker`).

## Regenerate

With Docker running and hosts configured:

```bash
npm install --no-save playwright
npx playwright install chromium
node scripts/capture-media.mjs
```

Requires [ffmpeg](https://ffmpeg.org/) on your PATH for `demo.gif`. GIF source frames are stored in `gif-frames/` (gitignored).
