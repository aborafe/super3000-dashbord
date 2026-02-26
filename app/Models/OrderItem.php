<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $qty
 * @property float|string $price
 * @property float|string|null $base_price
 * @property float|string $cost
 * @property float|string $line_total
 * @property-read float $unit_discount
 * @property-read float $discount_total
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price',
        'base_price',
        'cost',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:2',
        'base_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function getUnitDiscountAttribute(): float
    {
        $basePrice = (float) ($this->base_price ?? $this->price);
        $price = (float) $this->price;

        if ($basePrice <= $price) {
            return 0.0;
        }

        return round($basePrice - $price, 2);
    }

    public function getDiscountTotalAttribute(): float
    {
        $qty = max(0, (int) $this->qty);

        if ($qty === 0) {
            return 0.0;
        }

        return round($this->unit_discount * $qty, 2);
    }
}
