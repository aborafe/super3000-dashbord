# CP4 Inventory Master Sync Report

Date: 2026-02-19  
Branch: `hardening/v2-full-system`

## Scope Delivered
- Added `InventoryService` as the single stock mutation layer.
- Adopted `product_stocks` as source of truth for stock.
- Ensured `products.stock_qty` is derived/synced from `product_stocks`.
- API order creation now:
  - reads available stock from default warehouse
  - deducts from default warehouse only
  - records inventory movement entries for deductions
- Product create/update admin flows now map stock changes through warehouse movements instead of direct raw stock edits.
- Admin inventory movement flow now uses service-based stock adjustments.
- Added bootstrapping/backfill migrations:
  - `2026_02_20_000010_set_default_inventory_warehouse_setting.php`
  - `2026_02_20_000011_backfill_product_stocks_from_products.php`
- Added reconcile operational command:
  - `php artisan inventory:reconcile`
  - `php artisan inventory:reconcile --dry-run`

## Validation
- Migrations applied successfully.
- Reconcile dry-run returned zero mismatches on current dataset.
- Full test suite passed (`51 passed`).

## Operational Notes
- Default warehouse key: `inventory.default_warehouse_id` in settings.
- Any future stock write should go through `InventoryService` or inventory movement flows, not direct manual updates.

## Residual Risks
1. Legacy external scripts that directly mutate `products.stock_qty` can still cause drift.
2. Realtime infrastructure (Reverb runtime) remains pending CP5.
