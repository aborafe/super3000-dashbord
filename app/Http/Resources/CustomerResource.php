<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isActive = (bool) $this->is_active;
        $status = $isActive ? 'approved' : 'pending';

        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'email' => (string) ($this->email ?? ''),
            'phone' => (string) ($this->phone ?? ''),
            'whatsapp' => (string) ($this->whatsapp ?? ''),
            'city' => (string) ($this->city ?? ''),
            'address' => (string) ($this->address ?? ''),
            'is_active' => $isActive,
            'status' => $status,
            'approval_status' => $status,
            'permissions' => [
                'can_view_prices' => $isActive,
                'can_checkout' => $isActive,
            ],
        ];
    }
}
