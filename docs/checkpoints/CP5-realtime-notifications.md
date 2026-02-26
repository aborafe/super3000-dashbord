# CP5 Realtime Notifications Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Scope Delivered
- Enabled queued notification delivery for realtime channels:
  - `app/Notifications/AdminMessageNotification.php`
  - `app/Notifications/OrderStatusChanged.php`
- Unified broadcast channels for both actors:
  - users receive on `user.{id}`
  - customers receive on `customer.{id}`
- Added user broadcast channel mapping:
  - `app/Models/User.php` now exposes `receivesBroadcastNotificationsOn()`.
- Added web broadcast auth endpoint for dashboard sockets:
  - `POST /broadcasting/auth` protected by `web + auth`.
  - Kept API broadcast auth endpoint for customer mobile flows:
    - `POST /api/broadcasting/auth` with `auth:sanctum + customer.token`.
- Fixed notifications dropdown rendering defect:
  - removed invalid `</li>1` artifact in `resources/views/layouts/admin.blade.php`.
- Added websocket-first + polling-fallback notification updates in dashboard UI:
  - `public/js/ui/dropdowns.js` now uses Echo/Pusher when enabled, with polling only on disconnect/fallback.
- Added runtime broadcast metadata to dashboard notification list container (channel/auth/host/port/key/scheme).
- Set `.env.example` broadcast default to `reverb`.

## Validation
- Route verification includes both auth endpoints:
  - `GET|POST /broadcasting/auth`
  - `GET|POST /api/broadcasting/auth`
- Tests executed:
  - `php artisan test --filter=NotificationApiTest` (pass)
  - `php artisan test --filter=AdminNotificationsTest` (pass)
  - `php artisan test` (pass: `51 passed`)

## Operational Notes
- Dashboard realtime requires valid frontend websocket libraries (Echo + Pusher runtime) and a working broadcaster backend.
- Mobile/customer realtime remains on `customer.{id}` and uses `/api/broadcasting/auth`.

## Residual Risk / Blocker
1. `laravel/reverb` and `pusher/pusher-php-server` could not be installed in this environment due local Composer SSL CA issue (`curl error 60`).
2. Until those packages are installed, production websocket delivery on `reverb` cannot be considered fully activated end-to-end.
