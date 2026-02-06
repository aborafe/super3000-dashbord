<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_login_and_get_token(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'testing',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'token_type',
                'access_token',
                'user' => ['id', 'email'],
            ],
        ]);
    }
}
