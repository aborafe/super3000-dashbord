# CP3 API v2 Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Scope Delivered
- Introduced `api/v2` routes and versioned controller namespace (`Api\V2` wrappers).
- Active customer API contract now uses `v2`.
- Forced deprecation of `v1` with `410 Gone` for all `api/v1/*` paths.
- Registration is not exposed in v2 (`/api/v2/auth/register` removed).
- Orders v2 hardened with idempotency:
  - `Idempotency-Key` header required for `POST /api/v2/orders`.
  - replay with same key returns existing order (`200`) instead of duplicate insert.
  - DB-level unique constraint on `(customer_id, idempotency_key)`.
- Order resource includes tracking metadata with idempotency key for API clients.

## Migrations
- Added and applied:
  - `2026_02_20_000009_add_idempotency_key_to_orders_table.php`

## Testing
- Full suite passed:
  - `php artisan test`
  - Result: `51 passed`, `231 assertions`
- Added/updated coverage:
  - v1 deprecation response checks (`410`)
  - v2 registration unavailability (`404`)
  - v2 idempotency required check (`422`)
  - v2 duplicate create replay behavior (`200` existing order)

## Residual Risks
1. Any client still calling `v1` will fail immediately by design.
2. Realtime transport migration to Reverb is pending CP5.
