<?php

namespace Tests;

use App\Models\Customer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsCustomerApi(Customer $customer): static
    {
        $token = $this->issueCustomerApiToken($customer);

        return $this->withToken($token);
    }

    protected function issueCustomerApiToken(Customer $customer, string $deviceName = 'test-device'): string
    {
        $customer->tokens()->delete();
        $token = $customer->createToken($deviceName)->plainTextToken;

        $customer->forceFill([
            'token' => hash('sha256', $token),
            'is_active' => true,
        ])->save();

        return $token;
    }
}
