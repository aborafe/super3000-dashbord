<?php

namespace App\Models;

use App\Domain\Orders\OrderStatusStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_no
 * @property int|null $customer_id
 * @property int|null $user_id
 * @property string $status
 * @property string|null $customer_name
 * @property string|null $customer_email
 * @property string|null $customer_phone
 * @property string|null $customer_notes
 * @property array<string, mixed>|null $shipping_address
 * @property array<string, mixed>|null $billing_address
 * @property string|null $billing_payment_method
 * @property float|string $subtotal
 * @property float|string $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $normalized_status
 * @property-read float $paid_amount
 * @property-read float $due_amount
 * @property-read float $items_discount_total
 */
class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = OrderStatusStateMachine::STATUS_PENDING;
    public const STATUS_APPROVED = OrderStatusStateMachine::STATUS_APPROVED;
    public const STATUS_SHIPPED = OrderStatusStateMachine::STATUS_SHIPPED;
    public const STATUS_DELIVERED = OrderStatusStateMachine::STATUS_DELIVERED;
    public const STATUS_RETURNED = OrderStatusStateMachine::STATUS_RETURNED;
    public const STATUS_CANCELLED = OrderStatusStateMachine::STATUS_CANCELLED;
    public const STATUS_PAID = OrderStatusStateMachine::LEGACY_STATUS_PAID;

    protected $fillable = [
        'order_no',
        'idempotency_key',
        'user_id',
        'customer_id',
        'customer_notes',
        'status',
        'subtotal',
        'total',
        'shipping_address',
        'billing_address',
        'billing_payment_method',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public static function workflowStatuses(): array
    {
        return OrderStatusStateMachine::statuses();
    }

    public static function normalizeStatus(string $status): string
    {
        return OrderStatusStateMachine::normalize($status);
    }

    public function getNormalizedStatusAttribute(): string
    {
        return self::normalizeStatus((string) $this->status);
    }

    /**
     * @return array<int, string>
     */
    public function availableTransitions(): array
    {
        return OrderStatusStateMachine::nextStatuses((string) $this->status);
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        return OrderStatusStateMachine::canTransition((string) $this->status, $nextStatus);
    }

    public function transitionTo(string $nextStatus): bool
    {
        if (! $this->canTransitionTo($nextStatus)) {
            return false;
        }

        $this->status = self::normalizeStatus($nextStatus);

        return true;
    }

    public function setStatusAttribute(string $value): void
    {
        $this->attributes['status'] = self::normalizeStatus($value);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Recalculate totals based on items.
     */
    public function recalculateTotals(): void
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $subtotal = $items->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total = $subtotal;
    }
    // computed attributes for payment tracking
    public function getPaidAmountAttribute(): float
    {
        if (array_key_exists('paid_amount', $this->attributes)) {
            return (float) $this->attributes['paid_amount'];
        }

        if ($this->relationLoaded('paymentAllocations')) {
            return (float) $this->paymentAllocations->sum(fn (PaymentAllocation $allocation): float => (float) $allocation->amount);
        }

        return (float) $this->paymentAllocations()->sum('amount');
    }

    public function getDueAmountAttribute(): float
    {
        if ($this->normalized_status === self::STATUS_CANCELLED) {
            return 0.0;
        }

        return max(0, (float) $this->total - $this->paid_amount);
    }

    public function getAdjustmentsTotalAttribute(): float
    {
        $adjustments = $this->invoice_adjustments;

        if (is_array($adjustments)) {
            $discount = (float) ($adjustments['discount'] ?? 0);
            $deliveryFee = (float) ($adjustments['delivery_fee'] ?? 0);

            return $deliveryFee - $discount;
        }

        return (float) $this->total - (float) $this->subtotal;
    }

    public function getTotalWithAdjustmentsAttribute(): float
    {
        return (float) $this->total;
    }

    public function getItemsDiscountTotalAttribute(): float
    {
        if (array_key_exists('items_discount_total', $this->attributes)) {
            return (float) $this->attributes['items_discount_total'];
        }

        if ($this->relationLoaded('items')) {
            return round(
                (float) $this->items->sum(fn (OrderItem $item): float => (float) $item->discount_total),
                2
            );
        }

        $discount = $this->items()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN COALESCE(base_price, price) > price THEN (COALESCE(base_price, price) - price) * qty ELSE 0 END), 0) as discount_total'
            )
            ->value('discount_total');

        return round((float) $discount, 2);
    }
}
