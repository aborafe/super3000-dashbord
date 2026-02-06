<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect([
            ['name_en' => 'Electronics', 'name_ar' => 'إلكترونيات'],
            ['name_en' => 'Groceries', 'name_ar' => 'بقالة'],
            ['name_en' => 'Clothing', 'name_ar' => 'ملابس'],
        ])->map(function (array $data) {
            return Category::query()->firstOrCreate(
                ['name_en' => $data['name_en']],
                $data,
            );
        });

        $warehouses = collect([
            ['name' => 'Main Warehouse', 'location' => 'Head Office'],
            ['name' => 'Secondary Warehouse', 'location' => 'City Branch'],
        ])->map(function (array $data) {
            return Warehouse::query()->firstOrCreate(
                ['name' => $data['name']],
                $data,
            );
        });

        $partners = collect([
            [
                'name' => 'Wholesale Partner 1',
                'phone' => '01000000001',
                'email' => 'wholesale1@example.com',
                'role_type' => 'wholesale',
            ],
            [
                'name' => 'Retail Partner 1',
                'phone' => '01000000002',
                'email' => 'retail1@example.com',
                'role_type' => 'retail',
            ],
            [
                'name' => 'Mixed Partner',
                'phone' => '01000000003',
                'email' => 'mixed@example.com',
                'role_type' => 'both',
            ],
        ])->map(function (array $data) {
            return Partner::query()->firstOrCreate(
                ['phone' => $data['phone']],
                $data,
            );
        });

        $products = collect([
            [
                'name_en' => 'Laptop',
                'name_ar' => 'حاسوب محمول',
                'sku' => 'LAP-001',
                'price' => 1500_00 / 100,
                'cost' => 1200_00 / 100,
                'status' => 'active',
            ],
            [
                'name_en' => 'T-Shirt',
                'name_ar' => 'قميص',
                'sku' => 'TSH-001',
                'price' => 100_00 / 100,
                'cost' => 60_00 / 100,
                'status' => 'active',
            ],
            [
                'name_en' => 'Rice Bag',
                'name_ar' => 'كيس أرز',
                'sku' => 'RICE-001',
                'price' => 50_00 / 100,
                'cost' => 30_00 / 100,
                'status' => 'active',
            ],
        ])->map(function (array $data, int $index) use ($categories) {
            $category = $categories[$index % $categories->count()];

            return Product::query()->firstOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, [
                    'stock' => 0,
                    'category_id' => $category->id,
                ]),
            );
        });

        // Seed initial stock distribution
        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                ProductStock::query()->firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    ['qty' => 50],
                );

                // Keep aggregated stock in products table in sync
                $product->increment('stock', 50);
            }
        }
    }
}
