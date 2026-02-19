<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::factory()->count(10)->create();
        $warehouses = Warehouse::factory()->count(3)->create();
        Customer::factory()->count(10)->create();
        $inventory = app(InventoryService::class);
        $defaultWarehouse = $warehouses->first();

        if ($defaultWarehouse) {
            Setting::setValue('inventory.default_warehouse_id', (string) $defaultWarehouse->id);
        }

        $products = Product::factory()
            ->count(25)
            ->state(fn () => ['category_id' => $categories->random()->id])
            ->create();

        foreach ($products as $product) {
            $initialStock = (int) $product->stock_qty;
            $product->update(['stock_qty' => 0]);

            if ($initialStock > 0 && $defaultWarehouse) {
                $inventory->moveStock(
                    (int) $product->id,
                    (int) $defaultWarehouse->id,
                    $initialStock,
                    'Initial stock seed'
                );
            }
        }

        foreach (range(1, 20) as $i) {
            $product = $products->random();
            $warehouse = $warehouses->random();
            $type = $product->stock_qty <= 2 ? 'in' : collect(['in', 'out'])->random();
            $qty = random_int(1, 20);

            $availableQty = $inventory->availableInWarehouse((int) $product->id, (int) $warehouse->id);

            if ($type === 'out' && $availableQty < $qty) {
                $type = 'in';
            }

            $inventory->moveStock(
                (int) $product->id,
                (int) $warehouse->id,
                $type === 'in' ? $qty : -1 * $qty,
                $type === 'in' ? 'Initial stock' : 'Stock usage'
            );

            $product->refresh();
        }

        Setting::setValue('general.site_name', 'Super3000');
        Setting::setValue('general.support_email', 'support@super3000.test');
        Setting::setValue('general.support_phone', '+1 000 000 0000');
        Setting::setValue('appearance.theme', 'light');
        Setting::setValue('appearance.rtl', '0');
    }
}
