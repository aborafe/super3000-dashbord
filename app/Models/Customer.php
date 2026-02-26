<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $city
 * @property string|null $address
 * @property bool $is_active
 * @property string|null $token
 */
class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp',
        'city',
        'address',
        'password',
        'is_active',
        'token',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'customer.'.$this->getKey();
    }

    /**
     * Customer balance convention:
     * negative => debtor, positive => creditor.
     */
    public function getBalanceAttribute(): float
    {
        return (float) ($this->paid_total - $this->invoice_total);
    }

    public function getInvoiceTotalAttribute(): float
    {
        return (float) $this->orders()
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->sum('total');
    }

    public function getPaidTotalAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'paid')
            ->sum('amount');
    }
}
