<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'stock_qty',
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
