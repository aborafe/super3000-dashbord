<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
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

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'customer.'.$this->getKey();
    }

    /**
     * Compute the customer's balance: positive means owes money, negative means credit.
     *
     * @return float
     */
    public function getBalanceAttribute(): float
    {
        $totalOrders = $this->orders()->sum('total');
        $totalPayments = \App\Models\Payment::whereHas('order', function ($q) {
            $q->where('customer_id', $this->id);
        })->sum('amount');

        return (float) ($totalOrders - $totalPayments);
    }
}
