<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property float|string $price
 * @property int $stock_qty
 * @property bool $is_active
 * @property int|null $category_id
 * @property string|null $brand
 * @property string|null $made_in
 * @property string|null $description
 * @property string|null $cover_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'is_active',
        'category_id',
        // new fields
        'brand',
        'made_in',
        'description',
        'cover_image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    protected static function booted()
    {
        static::created(function (Product $product): void {
            if (! Schema::hasTable('product_stocks') || ! Schema::hasTable('warehouses')) {
                return;
            }

            $inventory = app(\App\Services\InventoryService::class);
            $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
            $initialQty = max(0, (int) $product->stock_qty);

            $stock = ProductStock::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $defaultWarehouseId,
                ],
                [
                    'qty' => 0,
                ]
            );

            if ((int) $stock->qty !== $initialQty) {
                $stock->qty = $initialQty;
                $stock->save();
            }

            $inventory->syncProductStockQty((int) $product->id);
        });

        static::deleting(function (Product $product) {
            if (! $product->isForceDeleting()) {
                return;
            }

            // remove cover file if exists
            if ($product->cover_image) {
                Storage::disk('public')->delete($product->cover_image);
            }
            // delete all associated images (files removed by model events)
            foreach ($product->images()->get() as $img) {
                $img->delete();
            }
        });
    }
}
