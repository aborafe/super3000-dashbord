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
