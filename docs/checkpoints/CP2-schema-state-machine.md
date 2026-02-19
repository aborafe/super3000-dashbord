# CP2 Schema + State Machine Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Scope Delivered
- Added domain state machine:
  - `app/Domain/Orders/OrderStatusStateMachine.php`
  - Allowed flow:
    - `pending -> approved -> shipped -> delivered`
    - `pending|approved -> cancelled`
    - `shipped|delivered -> returned`
- Enforced transition rules in admin updates:
  - `app/Http/Controllers/Admin/OrderController.php`
  - `app/Http/Requests/UpdateOrderStatusRequest.php`
- Normalized legacy status:
  - `paid -> approved` at domain/model/resource level.
- Added migrations:
  - `2026_02_20_000006_normalize_orders_status_workflow.php`
  - `2026_02_20_000007_add_soft_deletes_and_indexes_for_cp2.php`
  - `2026_02_20_000008_deduplicate_customer_contacts_and_enforce_uniques.php`
- Enabled soft delete on core entities:
  - `customers`, `products`, `categories`
- Preserved historical relations with soft-deleted entities via `withTrashed()`:
  - order -> customer
  - order_item -> product
  - product_stock -> product
  - product -> category
- Added unread notification indexes for faster dashboard/mobile notification reads.
- Enforced customer contact uniqueness:
  - unique `customers.email`
  - unique `customers.phone`
  - safe dedup pass executed before applying unique constraints.
- Updated dashboard/order/invoice UI + translations to align with `approved` status vocabulary.
- Added transition coverage tests in `OrderTotalsTest`.

## Verification
- Migrations:
  - `php artisan migrate --force` -> success.
- Automated tests:
  - `php artisan test` -> `48 passed`, `220 assertions`.
- Manual DB checks:
  - `customers_phone_unique` and `customers_email_unique` exist.
  - notification unread indexes exist.
  - legacy `paid` values normalized in order statuses.

## Residual Risks
1. `api/v1/auth/register` still present (planned in CP3 API v2 cutover).
2. Realtime transport still not switched to Reverb yet (planned in CP5).
