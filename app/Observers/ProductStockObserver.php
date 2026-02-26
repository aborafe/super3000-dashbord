<?php

namespace App\Observers;

use App\Models\ProductStock;
use App\Models\User;
use App\Notifications\LowStockAlert;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Notification;

class ProductStockObserver
{
    public function saved(ProductStock $productStock): void
    {
        app(InventoryService::class)->syncProductStockQty((int) $productStock->product_id);

        if (! $productStock->wasChanged('qty') && ! $productStock->wasRecentlyCreated) {
            return;
        }

        $threshold = (int) config('super3000.low_stock_threshold', 10);
        if ((int) $productStock->qty <= 0) {
            return;
        }

        $previousQty = $productStock->getOriginal('qty');
        $previousWasLow = $previousQty !== null && (int) $previousQty < $threshold;

        if ($productStock->qty >= $threshold || $previousWasLow) {
            return;
        }

        $productStock->loadMissing(['product', 'warehouse']);

        $admins = User::role('admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new LowStockAlert($productStock));
    }

    public function deleted(ProductStock $productStock): void
    {
        app(InventoryService::class)->syncProductStockQty((int) $productStock->product_id);
    }
}
