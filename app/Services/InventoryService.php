<?php

namespace App\Services;

use App\Exceptions\StockConflictException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function resolveDefaultWarehouseId(): int
    {
        $configured = Setting::getValue('inventory.default_warehouse_id');
        $warehouseId = is_numeric($configured) ? (int) $configured : 0;

        if ($warehouseId > 0) {
            $warehouse = Warehouse::query()
                ->whereKey($warehouseId)
                ->where('is_active', true)
                ->first();

            if ($warehouse) {
                return $warehouse->id;
            }
        }

        $fallbackWarehouse = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $fallbackWarehouse) {
            $fallbackWarehouse = Warehouse::query()->create([
                'name' => 'Default Warehouse',
                'location' => 'System',
                'is_active' => true,
            ]);
        }

        Setting::setValue('inventory.default_warehouse_id', (string) $fallbackWarehouse->id);

        return (int) $fallbackWarehouse->id;
    }

    public function availableInWarehouse(int $productId, int $warehouseId): int
    {
        return (int) $this->lockProductStock($productId, $warehouseId)->qty;
    }

    public function moveStock(int $productId, int $warehouseId, int $delta, ?string $note = null): InventoryMovement
    {
        if ($delta === 0) {
            throw new RuntimeException('Inventory delta cannot be zero.');
        }

        return DB::transaction(function () use ($productId, $warehouseId, $delta, $note): InventoryMovement {
            $productStock = $this->lockProductStock($productId, $warehouseId);
            $nextQty = (int) $productStock->qty + $delta;

            if ($nextQty < 0) {
                throw new StockConflictException('Insufficient stock');
            }

            $productStock->qty = $nextQty;
            $productStock->save();

            $movement = InventoryMovement::query()->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $delta > 0 ? 'in' : 'out',
                'qty' => abs($delta),
                'note' => $note,
            ]);

            $this->syncProductStockQty($productId);

            return $movement;
        });
    }

    public function syncProductStockQty(int $productId): void
    {
        $totalQty = (int) ProductStock::query()
            ->where('product_id', $productId)
            ->sum('qty');

        Product::query()
            ->whereKey($productId)
            ->update(['stock_qty' => $totalQty]);
    }

    public function reconcileAllProducts(): int
    {
        $updated = 0;

        Product::query()
            ->select('id', 'stock_qty')
            ->orderBy('id')
            ->chunkById(200, function ($products) use (&$updated): void {
                foreach ($products as $product) {
                    $masterQty = (int) ProductStock::query()
                        ->where('product_id', $product->id)
                        ->sum('qty');

                    if ((int) $product->stock_qty !== $masterQty) {
                        Product::query()
                            ->whereKey($product->id)
                            ->update(['stock_qty' => $masterQty]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function lockProductStock(int $productId, int $warehouseId): ProductStock
    {
        $stock = ProductStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        ProductStock::query()->create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'qty' => 0,
        ]);

        return ProductStock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
