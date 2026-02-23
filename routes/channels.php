<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function (User $user, int $id): bool {
    return (int) $user->getKey() === (int) $id;
});

Broadcast::channel('customer.{id}', function (Customer $customer, int $id): bool {
    return (int) $customer->getKey() === (int) $id;
});
