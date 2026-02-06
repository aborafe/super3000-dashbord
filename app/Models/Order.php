<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'order_no',
        'partner_id',
        'status',
        'payment_status',
        'total',
        'cost_total',
        'profit',
        'created_by',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recalculate MVP totals: total, cost_total, and profit.
     */
    public function recalculateTotals(): void
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $total = $items->sum('line_total');
        $costTotal = $items->sum(function (OrderItem $item): float {
            return $item->qty * $item->cost;
        });

        $this->total = $total;
        $this->cost_total = $costTotal;
        $this->profit = $total - $costTotal;
    }
}
