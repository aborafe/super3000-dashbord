<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $order_id
 * @property int|null $customer_id
 * @property float|string $amount
 * @property string $method
 * @property string $status
 * @property string|null $source
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read float $allocated_amount
 * @property-read float $unallocated_amount
 * @property-read Order|null $order
 * @property-read Customer|null $customer
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentAllocation> $allocations
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'amount',
        'method',
        'status',
        'source',
        'notes',
        'created_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getAllocatedAmountAttribute(): float
    {
        if (array_key_exists('allocated_amount', $this->attributes)) {
            return (float) $this->attributes['allocated_amount'];
        }

        if ($this->relationLoaded('allocations')) {
            return (float) $this->allocations->sum(fn (PaymentAllocation $allocation): float => (float) $allocation->amount);
        }

        return (float) $this->allocations()->sum('amount');
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return max(0, (float) $this->amount - $this->allocated_amount);
    }
}
