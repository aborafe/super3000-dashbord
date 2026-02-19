<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:reconcile {--dry-run : Show mismatches without updating}', function () {
    $mismatches = 0;

    DB::table('products')
        ->select('id', 'stock_qty')
        ->orderBy('id')
        ->chunkById(200, function ($products) use (&$mismatches): void {
            foreach ($products as $product) {
                $masterQty = (int) DB::table('product_stocks')
                    ->where('product_id', $product->id)
                    ->sum('qty');

                if ((int) $product->stock_qty !== $masterQty) {
                    $mismatches++;
                }
            }
        });

    if ((bool) $this->option('dry-run')) {
        $this->info("inventory:reconcile dry-run finished. Mismatches: {$mismatches}");

        return;
    }

    $updated = app(\App\Services\InventoryService::class)->reconcileAllProducts();

    $this->info("inventory:reconcile finished. Updated products: {$updated}. Previous mismatches: {$mismatches}");
})->purpose('Reconcile products.stock_qty from product_stocks master quantities');
