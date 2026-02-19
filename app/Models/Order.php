<?php

namespace App\Models;

use App\Domain\Orders\OrderStatusStateMachine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'user_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_whatsapp',
        'customer_address',
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
        return (float) $this->payments()->sum('amount');
    }

    public function getDueAmountAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
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
}
