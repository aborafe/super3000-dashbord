# Implementation Log

Branch: `hardening/v2-full-system`  
Owner: Codex  
Started: 2026-02-19

## Checkpoint Timeline

### CP0-baseline-lock
- Status: completed
- Summary:
  - Branched from current working state without cleaning existing changes.
  - Captured runtime baseline and route surface.
  - Prepared smoke matrix and known issues snapshot.
  - Ran full automated tests as baseline (`45 passed`).
- Risks observed:
  - API v1 still exposes customer product write endpoints.
  - Dashboard route protection is broad (`dashboard.view`) and not action-specific.
  - Broadcasting driver is `log`, not realtime websocket.
  - Order status domain and DB enum can drift between environments.
- Rollback:
  - Use branch tag `CP0-baseline-lock`.

### CP1-critical-security
- Status: completed
- Summary:
  - Removed customer write access to catalog on API (`POST/PUT /api/v1/products` removed).
  - Removed debug endpoint `/api/hello`.
  - Added throttling profiles:
    - `web-login` applied to `POST /login`.
    - `api-sensitive` applied to sensitive API write operations.
  - Replaced broad dashboard route access with action-level permissions in admin routes.
  - Hardened sensitive `FormRequest::authorize()` methods to enforce permission checks.
  - Added explicit API handlers for `404` and `405` to stop leaking method errors as `500`.
  - Added notification permissions (`notifications.view`, `notifications.send`) and wired roles.
- Validation:
  - `php artisan test` => `46 passed`.
  - Route verification confirms:
    - No customer write endpoints for `api/v1/products`.
    - `POST /login` throttled by `web-login`.
    - Sensitive API writes throttled by `api-sensitive`.
    - Admin sensitive routes include `can:*` middleware per action.
- Risks observed:
  - `api/v1/auth/register` is still present until API v2 cutover stage.
  - Realtime driver remains `log` until CP5.
- Rollback:
  - Use branch tag `CP1-critical-security`.

### CP2-schema-state-machine
- Status: completed
- Summary:
  - Added domain state machine for orders (`pending -> approved -> shipped -> delivered`, with `cancelled/returned` rules).
  - Enforced transition rules in admin status update endpoint and validation.
  - Normalized legacy status values (`paid -> approved`) at model/resource/domain level.
  - Added schema migration to move `orders.status` to workflow-safe string values and remap old values.
  - Added `softDeletes` for `customers`, `products`, and `categories`.
  - Updated relations to keep historical references visible with soft-deleted records (`withTrashed()`).
  - Added/optimized indexes for unread notifications lookups.
  - Enforced customer contact uniqueness with safe dedup migration and unique indexes on `email` and `phone`.
  - Updated dashboard/order/invoice views and translations to use `approved` flow.
  - Added transition tests for invalid skips and cancel-after-shipping protection.
- Validation:
  - `php artisan migrate --force` => migrations applied successfully.
  - `php artisan test` => `48 passed`.
- Risks observed:
  - `api/v1/auth/register` still active until API v2 cutover.
  - Realtime delivery still waiting CP5 (`BROADCAST_CONNECTION=log` before runtime switch).
- Rollback:
  - Use branch tag `CP2-schema-state-machine`.

### CP3-api-v2
- Status: completed
- Summary:
  - Launched `api/v2` surface for customers:
    - `POST /api/v2/auth/login`
    - `POST /api/v2/auth/logout`
    - `GET /api/v2/me`
    - `GET /api/v2/categories`
    - `GET /api/v2/products`
    - `GET /api/v2/products/{product}`
    - `GET|POST /api/v2/orders` (+ ownership protection remains)
    - notifications endpoints (`list/unread/read/read-all`)
  - Disabled `api/v1/*` immediately with `410 Gone` response and migration message.
  - Removed registration path from active API contract (no `/api/v2/auth/register`).
  - Added idempotency for order creation:
    - `Idempotency-Key` header required on `/api/v2/orders`.
    - request replay with same key returns existing order safely.
    - DB unique guard added on `(customer_id, idempotency_key)`.
  - Added lightweight V2 controller namespace wrappers for stable API versioning structure.
  - Updated tests from v1 to v2 and added v1 deprecation + idempotency coverage.
- Validation:
  - `php artisan migrate --force` => applied `000009` idempotency migration.
  - `php artisan test` => `51 passed`.
- Risks observed:
  - Mobile clients must switch immediately to `/api/v2/*` (v1 hard-stopped by design).
  - Realtime infra switch to Reverb still pending CP5.
- Rollback:
  - Use branch tag `CP3-api-v2`.

### CP4-inventory-master-sync
- Status: completed
- Summary:
  - Implemented centralized inventory domain service (`InventoryService`) with warehouse-aware stock moves.
  - Switched stock operations to `product_stocks` as master source.
  - Synced display stock (`products.stock_qty`) from master quantities after each movement/reconcile.
  - Updated API order creation to validate/deduct from configured default warehouse only.
  - Added default warehouse bootstrap + data backfill migrations:
    - `000010_set_default_inventory_warehouse_setting`
    - `000011_backfill_product_stocks_from_products`
  - Added `inventory:reconcile` artisan command (with `--dry-run`) to repair drift.
  - Updated product create/update and admin inventory movement flows to use service-based stock updates.
  - Added guardrails to keep product stock editing aligned with master warehouse logic.
- Validation:
  - `php artisan migrate --force` => `000010` and `000011` applied.
  - `php artisan inventory:reconcile --dry-run` => `Mismatches: 0`.
  - `php artisan inventory:reconcile` => `Updated products: 0`.
  - `php artisan test` => `51 passed`.
- Risks observed:
  - Realtime delivery is still pending CP5 infrastructure switch.
  - Old data integrations that write directly to `products.stock_qty` must move to service/warehouse movements.
- Rollback:
  - Use branch tag `CP4-inventory-master-sync`.

### CP5-realtime-notifications
- Status: completed (with environment blocker noted)
- Summary:
  - Enabled queued notifications for realtime delivery (`ShouldQueue`) on:
    - `AdminMessageNotification`
    - `OrderStatusChanged`
  - Enabled broadcast delivery for both users and customers.
  - Added explicit user notification channel mapping (`user.{id}`) and strict typed channel authorization.
  - Added web broadcast auth route (`/broadcasting/auth`) while preserving API broadcast auth for customers (`/api/broadcasting/auth`).
  - Fixed dashboard notifications dropdown rendering bug (`</li>1` artifact).
  - Added websocket-first notification updates in dashboard (`Echo/Pusher`) with polling fallback only when realtime is disconnected/unavailable.
  - Added realtime connection metadata to notifications dropdown DOM for runtime bootstrap.
  - Set `.env.example` default broadcaster to `reverb`.
- Validation:
  - `php artisan route:list` confirms both broadcast auth endpoints exist.
  - `php artisan test --filter=NotificationApiTest` => pass.
  - `php artisan test --filter=AdminNotificationsTest` => pass.
  - `php artisan test` => `51 passed`.
- Risks observed:
  - Composer SSL/CA issue (`curl error 60`) blocks installing `laravel/reverb` and `pusher/pusher-php-server` in this environment.
  - Reverb runtime can only be fully enabled after fixing Composer CA and installing missing broadcast packages.
- Rollback:
  - Use branch tag `CP5-realtime-notifications`.

### CP6-dashboard-permissions
- Status: completed
- Summary:
  - Enforced strict admin invoice item edits by order lifecycle:
    - allowed only for `pending` and `approved`.
    - blocked for `shipped`, `delivered`, `cancelled`, `returned`.
  - Added inventory reconciliation for admin invoice item changes:
    - calculates product-level quantity deltas from existing vs requested lines.
    - applies stock mutations through `InventoryService` on default warehouse.
    - rejects save on stock conflicts with user-facing validation errors.
  - Kept invoice totals recalculation and item persistence inside transaction flow.
  - Updated admin order page UX to reflect lock state:
    - disabled invoice editing controls on non-editable statuses.
    - added warning message for locked invoice states.
  - Normalized locale-safe route generation in order show/details/items/status/print actions.
  - Extended invoice editor feature tests for stock reconciliation and post-shipping lock behavior.
- Validation:
  - `php artisan test --filter=AdminOrderInvoiceEditorTest` => pass.
  - `php artisan test` => `52 passed`.
- Risks observed:
  - CP5 composer SSL blocker still prevents full Reverb package activation in this environment.
- Rollback:
  - Use branch tag `CP6-dashboard-permissions`.
