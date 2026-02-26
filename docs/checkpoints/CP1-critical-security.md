# CP1 Critical Security Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Scope Delivered
- Closed customer catalog write access on API v1:
  - Removed `POST /api/v1/products`
  - Removed `PUT /api/v1/products/{product}`
- Removed non-production debug endpoint:
  - Removed `GET /api/hello`
- Added throttling:
  - `web-login` on `POST /login`
  - `api-sensitive` on:
    - `POST /api/v1/auth/register`
    - `POST /api/v1/auth/logout`
    - `POST /api/v1/orders`
    - `PATCH /api/v1/notifications/read-all`
    - `PATCH /api/v1/notifications/{id}/read`
- Applied route-level permissions in dashboard routes (`can:*`) by action:
  - products, categories, customers, orders, order status, inventory, warehouses
  - users/roles/activity logs
  - settings
  - notifications (new permissions)
- Strengthened sensitive `FormRequest::authorize()` checks.
- Fixed API error mapping:
  - `NotFoundHttpException` -> 404 envelope
  - `MethodNotAllowedHttpException` -> 405 envelope

## Permissions Added
- `notifications.view`
- `notifications.send`

## Verification
- `php artisan test` passed:
  - `46` tests
  - `214` assertions
- Route checks:
  - `api/v1/products` is now read-only for customers.
  - `login.store` includes `ThrottleRequests:web-login`.
  - Sensitive API write endpoints include `ThrottleRequests:api-sensitive`.
  - Admin sensitive routes include expected `Authorize:*` middleware.

## Residual Risks
1. `api/v1/auth/register` still exists (planned removal/deprecation in API v2 stage).
2. Broadcasting is still configured as `log` (realtime pending CP5).
