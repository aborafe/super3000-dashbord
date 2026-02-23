<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerProfileUpdatedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
                'customer' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'is_active',
                    'status',
                    'approval_status',
                    'permissions' => ['can_view_prices', 'can_checkout'],
                ],
            ],
            'meta' => ['message'],
            'errors',
        ]);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.customer.status', 'approved');
        $response->assertJsonPath('data.customer.permissions.can_view_prices', true);
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

    public function test_legacy_customer_without_password_cannot_login(): void
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

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Invalid credentials');

        $customer->refresh();
        $this->assertNull($customer->password);
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
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.permissions.can_checkout', true);
    }

    public function test_me_update_profile_updates_customer_data_and_returns_profile(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '+15550000001',
            'whatsapp' => '+15550000002',
            'city' => 'Old City',
            'address' => 'Old Address',
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)->patchJson('/api/v2/me', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '+15551112222',
            'whatsapp' => '+15553334444',
            'city' => 'Riyadh',
            'address' => 'New Street 10',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('meta.message', 'Profile updated');
        $response->assertJsonPath('data.name', 'New Name');
        $response->assertJsonPath('data.email', 'new@example.com');
        $response->assertJsonPath('data.phone', '+15551112222');
        $response->assertJsonPath('data.whatsapp', '+15553334444');
        $response->assertJsonPath('data.city', 'Riyadh');
        $response->assertJsonPath('data.address', 'New Street 10');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '+15551112222',
            'whatsapp' => '+15553334444',
            'city' => 'Riyadh',
            'address' => 'New Street 10',
        ]);
    }

    public function test_me_update_profile_notifies_admins_with_old_and_new_data(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = Customer::factory()->create([
            'name' => 'Profile Owner',
            'email' => 'owner@example.com',
            'phone' => '+15554440000',
            'whatsapp' => '+15554440001',
            'city' => 'Jeddah',
            'address' => 'Address 1',
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)->patchJson('/api/v2/me', [
            'name' => 'Profile Owner',
            'email' => 'owner@example.com',
            'phone' => '+15554440000',
            'whatsapp' => '+15554449999',
            'city' => 'Riyadh',
            'address' => 'Address 2',
        ]);

        $response->assertOk();

        Notification::assertSentTo(
            $admin,
            CustomerProfileUpdatedNotification::class,
            function (CustomerProfileUpdatedNotification $notification): bool {
                return $notification->oldProfile['city'] === 'Jeddah'
                    && $notification->newProfile['city'] === 'Riyadh'
                    && $notification->oldProfile['address'] === 'Address 1'
                    && $notification->newProfile['address'] === 'Address 2'
                    && in_array('city', $notification->changedFields, true)
                    && in_array('address', $notification->changedFields, true)
                    && in_array('whatsapp', $notification->changedFields, true);
            }
        );
    }

    public function test_register_creates_inactive_customer_pending_activation(): void
    {
        $response = $this->postJson('/api/v2/auth/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@example.com',
            'phone' => '+15550001111',
            'whatsapp' => '+15550001112',
            'city' => 'Riyadh',
            'address' => 'Warehouse 24, Zone B',
            'password' => 'RegisterPass123',
            'password_confirmation' => 'RegisterPass123',
            'device_name' => 'pixel-8',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('meta.message', 'Registration submitted. Awaiting activation.');
        $response->assertJsonPath('data.customer.email', 'mobile@example.com');
        $response->assertJsonPath('data.customer.phone', '+15550001111');
        $response->assertJsonPath('data.customer.whatsapp', '+15550001112');
        $response->assertJsonPath('data.customer.city', 'Riyadh');
        $response->assertJsonPath('data.customer.address', 'Warehouse 24, Zone B');
        $response->assertJsonPath('data.customer.is_active', false);
        $response->assertJsonPath('data.customer.status', 'pending');
        $response->assertJsonPath('data.customer.permissions.can_checkout', false);

        $this->assertDatabaseHas('customers', [
            'email' => 'mobile@example.com',
            'phone' => '+15550001111',
            'whatsapp' => '+15550001112',
            'city' => 'Riyadh',
            'address' => 'Warehouse 24, Zone B',
            'is_active' => false,
        ]);

        $customer = Customer::query()->where('email', 'mobile@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('RegisterPass123', (string) $customer->password));
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
