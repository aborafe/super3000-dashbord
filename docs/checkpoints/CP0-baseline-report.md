# CP0 Baseline Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Runtime Baseline
- Environment: `local`
- Debug: `enabled`
- Database driver: `mysql`
- Queue driver: `database`
- Session driver: `database`
- Broadcast driver: `log`

## API Surface Snapshot (High Level)
- `api/v1/auth/login|logout|register`
- `api/v1/me`
- `api/v1/categories`
- `api/v1/products` (includes write endpoints)
- `api/v1/orders`
- `api/v1/notifications`
- No `api/v2` routes yet.

## Dashboard Surface Snapshot (High Level)
- Admin routes grouped under locale prefix (`{locale}/admin`).
- Order editor/status endpoints enabled.
- Notification compose/read endpoints enabled.

## Known Defects At Baseline
1. Customer API can create/update products (`api/v1/products`).
2. Web route authorization is too coarse (mostly `can:dashboard.view`).
3. `api/v1/auth/register` still enabled, while target policy is dashboard-only customer creation.
4. Realtime delivery not active (`BROADCAST_CONNECTION=log`).
5. Notification dropdown markup contains stray token `</li>1` in admin layout.
6. API debug leakage risk when debug mode is on for non-local deployments.

## Smoke Matrix For All Checkpoints
- Web login page load.
- Web admin login success.
- Orders list open.
- Order details open.
- Invoice print view open.
- Notification list open.
- Notification compose submit.
- API create order.
- API list notifications.

