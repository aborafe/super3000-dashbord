# Production Runbook

## 1) Pre-Deployment Checklist
- Confirm DB backup is taken before migrations.
- Confirm `.env` uses production-safe values:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `BROADCAST_CONNECTION=reverb` (or approved broadcaster)
  - `QUEUE_CONNECTION=database` (or redis)
- Ensure `ADMIN_SEED_PASSWORD` is set only when intentionally creating/updating admin seed user.
- Ensure demo/reference seeders are not executed in production.

## 2) Deployment Steps
1. Pull release branch/tag.
2. Install dependencies:
   - `composer install --no-dev --optimize-autoloader`
3. Run migrations:
   - `php artisan migrate --force`
4. Clear/refresh caches:
   - `php artisan optimize:clear`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
5. Run smoke checks:
   - admin login
   - create order via API v2
   - update order status in dashboard
   - print invoice
   - send/read notifications

## 3) Queue and Realtime Operations
- Start queue workers (system service/supervisor):
  - `php artisan queue:work --tries=3 --timeout=120`
- Start Reverb server (after package/config readiness):
  - `php artisan reverb:start`
- Ensure workers and reverb are supervised and auto-restarted.

## 4) Monitoring Signals
- Watch for:
  - HTTP `5xx` rate
  - order creation success rate
  - queue backlog growth
  - notification latency/failures
  - stock drift alarms (run `inventory:reconcile --dry-run`)

## 5) Rollback Procedure
1. Put app in maintenance mode if impact is high.
2. Re-deploy previous stable tag/checkpoint.
3. If schema rollback is required, execute approved down/rollback scripts.
4. Clear caches and restart workers.
5. Re-run smoke checks and reopen traffic.

## 6) Incident Notes
- If realtime is down, dashboard falls back to polling for notification updates.
- If Composer SSL CA blocks package installs, resolve host CA trust first before enabling new production dependencies.
