<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_success_returns_token_and_user_envelope(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'customer1@example.com',
            'phone' => '+15551230001',
            'password' => Hash::make('SecurePass123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => $customer->email,
            'password' => 'SecurePass123',
            'device_name' => 'android-phone',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'token',
                'access_token',
                'customer' => ['id', 'name', 'email', 'phone', 'is_active'],
            ],
            'meta' => ['message'],
            'errors',
        ]);
        $response->assertJsonPath('status', true);
        $token = (string) $response->json('data.token');
        $this->assertDatabaseHas('customers', [
            'email' => $customer->email,
            'token' => hash('sha256', $token),
        ]);
    }

    public function test_login_failure_returns_unauthorized_envelope(): void
    {
        Customer::factory()->create([
            'email' => 'customer2@example.com',
            'phone' => '+15551230002',
            'password' => Hash::make('SecurePass123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'customer2@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Invalid credentials');
    }

    public function test_login_accepts_phone_when_customer_exists(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'customer-phone@example.com',
            'phone' => '+1 (555) 777-8899',
            'password' => Hash::make('CustomerPhone123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'phone' => '15557778899',
            'password' => 'CustomerPhone123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.customer.email', $customer->email);
    }

    public function test_legacy_customer_can_login_once_and_password_is_upgraded(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'legacy@example.com',
            'phone' => '01018919997',
            'password' => null,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => $customer->email,
            'password' => '01018919997',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', true);

        $customer->refresh();
        $this->assertNotNull($customer->password);
        $this->assertTrue(Hash::check('01018919997', (string) $customer->password));
    }

    public function test_login_fails_when_customer_record_missing(): void
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'identifier' => 'missing@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Invalid credentials');
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/me');

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Unauthenticated');
    }

    public function test_me_returns_authenticated_profile(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAsCustomerApi($customer)->getJson('/api/v2/me');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.id', (string) $customer->id);
        $response->assertJsonPath('data.email', $customer->email);
    }

    public function test_register_endpoint_is_not_available_on_v2(): void
    {
        $response = $this->postJson('/api/v2/auth/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'phone' => '+15550001111',
            'password' => 'RegisterPass123',
            'password_confirmation' => 'RegisterPass123',
            'device_name' => 'pixel-8',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Resource not found');
        $this->assertDatabaseMissing('customers', ['email' => 'mobile@example.com']);
    }

    public function test_v1_endpoints_return_gone_response(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'legacy@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(410);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'API v1 is deprecated. Please migrate to /api/v2.');
    }

    public function test_v1_register_endpoint_returns_gone_response(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'BlockedPass123',
            'password_confirmation' => 'BlockedPass123',
        ]);

        $response->assertStatus(410);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'API v1 is deprecated. Please migrate to /api/v2.');
        $this->assertDatabaseMissing('customers', ['email' => 'blocked@example.com']);
    }

    public function test_protected_api_rejects_invalid_customer_token(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'secure@example.com',
            'is_active' => true,
            'token' => hash('sha256', 'another-token'),
        ]);

        $token = $customer->createToken('mobile-device')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v2/me');

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Invalid token');
    }
}
