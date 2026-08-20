## Summary

Add **Spanish** as another locale. English and Arabic already ship; French is tracked in #1.

## Tasks

- [ ] Copy `lang/en/app.php` → `lang/es/app.php` and translate all strings (including `api_operator` guided agent menus/flows)
- [ ] Register `es` in `config/locales.php` with label + direction `ltr`
- [ ] Document in README under "Add a new language" (Spanish included)
- [ ] Optional: PHPUnit assertion that `Locales::available()` includes `es`

## Out of scope

- Filament vendor translations
- Machine translation dumps — human-readable Spanish please
- Closing #1 (French can land separately)

## How to test

1. Set `APP_AVAILABLE_LOCALES=en,ar,es` in `.env`
2. Switch locale on landing/login → Spanish strings appear
3. Open guided agent on `/dashboard` → Spanish menu labels

## Acceptance

PR passes CI (`php artisan test`).

See [ROADMAP](docs/ROADMAP.md) — contributor-friendly, tenant-kit only.
