# Super3000 Backend

Laravel 12 backend for Super3000 dashboard + customer mobile API.

## Core Architecture
- Admin dashboard (web, locale-aware routes: `/{locale}/admin/*`)
- Customer API v2 (`/api/v2/*`)
- Sanctum token auth for customers
- Role/permission model for dashboard users (Spatie)
- Order workflow state machine:
  - `pending -> approved -> shipped -> delivered`
  - `pending|approved -> cancelled`
  - `shipped|delivered -> returned`
- Inventory master source:
  - `product_stocks` is source of truth
  - `products.stock_qty` is derived display field

## Local Setup
```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed
php artisan serve
```

## Tests
```bash
php artisan test
```

## API
- Documentation: `API_DOCUMENTATION.md`
- `v1` endpoints are deprecated and return `410 Gone`.
- Use `v2` only.

## Realtime and Queues
- Notifications support broadcast + database channels.
- Dashboard notification dropdown uses websocket-first with polling fallback.
- Run queue workers in production.
- Reverb operations are documented in `docs/production-runbook.md`.

## Production Operations
- Deployment/rollback/runbook:
  - `docs/production-runbook.md`
- Checkpoint implementation log:
  - `docs/implementation-log.md`
