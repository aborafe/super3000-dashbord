<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdmin(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.security.roles.store', ['locale' => 'en']), [
            'name' => 'auditor',
            'permissions' => ['orders.view', 'reports.view'],
        ]);

        $response->assertRedirect(route('admin.security.roles.index', ['locale' => 'en']));
        $response->assertSessionHas('success');

        $role = Role::query()->where('name', 'auditor')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('orders.view'));
        $this->assertTrue($role->hasPermissionTo('reports.view'));
    }

    public function test_admin_can_delete_unassigned_custom_role(): void
    {
        $admin = $this->createAdmin();
        $role = Role::query()->create([
            'name' => 'temp_role',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.security.roles.destroy', [
            'locale' => 'en',
            'role' => $role->id,
        ]));

        $response->assertRedirect(route('admin.security.roles.index', ['locale' => 'en']));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_role_assigned_to_users(): void
    {
        $admin = $this->createAdmin();
        $role = Role::query()->create([
            'name' => 'support_agent',
            'guard_name' => 'web',
        ]);

        $targetUser = User::factory()->create();
        $targetUser->assignRole($role);

        $response = $this->actingAs($admin)->delete(route('admin.security.roles.destroy', [
            'locale' => 'en',
            'role' => $role->id,
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_admin_can_assign_multiple_roles_to_user(): void
    {
        $admin = $this->createAdmin();

        $createResponse = $this->actingAs($admin)->post(route('admin.security.users.store', ['locale' => 'en']), [
            'name' => 'Multi Role User',
            'email' => 'multi-role@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['staff', 'manager'],
        ]);

        $createResponse->assertRedirect(route('admin.security.users.index', ['locale' => 'en']));

        $created = User::query()->where('email', 'multi-role@example.test')->firstOrFail();
        $this->assertEqualsCanonicalizing(['staff', 'manager'], $created->getRoleNames()->all());

        $updateResponse = $this->actingAs($admin)->put(route('admin.security.users.update', [
            'locale' => 'en',
            'user' => $created->id,
        ]), [
            'name' => 'Multi Role User',
            'email' => 'multi-role@example.test',
            'password' => '',
            'password_confirmation' => '',
            'roles' => ['staff'],
        ]);

        $updateResponse->assertRedirect(route('admin.security.users.index', ['locale' => 'en']));
        $this->assertEqualsCanonicalizing(['staff'], $created->fresh()->getRoleNames()->all());
    }

    public function test_staff_cannot_access_roles_create_page(): void
    {
        /** @var \App\Models\User $staff */
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $response = $this->actingAs($staff)->get(route('admin.security.roles.create', ['locale' => 'en']));

        $response->assertForbidden();
    }

    public function test_admin_can_open_user_edit_page_with_permission_overrides_section(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($admin)->get(route('admin.security.users.edit', [
            'locale' => 'en',
            'user' => $user->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Permission Overrides');
        $response->assertSeeText('Roles');
    }

    public function test_admin_can_open_user_profile_page_with_profile_layout(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create([
            'email' => 'profile-page@example.test',
        ]);
        $user->assignRole('staff');

        $response = $this->actingAs($admin)->get(route('admin.users.show', [
            'locale' => 'en',
            'user' => $user->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('User Profile');
        $response->assertSeeText('Permissions Overview');
        $response->assertSeeText('profile-page@example.test');
    }

    public function test_user_profile_page_hides_billing_and_connections_tabs(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($admin)->get(route('admin.users.show', [
            'locale' => 'en',
            'user' => $user->id,
        ]));

        $response->assertOk();
        $response->assertDontSeeText('Billing & Plans');
        $response->assertDontSeeText('Connections');
        $response->assertSeeText('Security');
    }

    public function test_admin_can_change_user_password_from_security_tab(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create([
            'email' => 'password-update@example.test',
            'password' => Hash::make('OldPass123'),
        ]);
        $user->assignRole('staff');

        $response = $this->actingAs($admin)->patch(route('admin.security.users.password', [
            'locale' => 'en',
            'user' => $user->id,
        ]), [
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ]);

        $response->assertRedirect(route('admin.users.show', ['locale' => 'en', 'user' => $user->id]));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewStrongPass123', (string) $user->fresh()->password));
    }

    public function test_admin_can_apply_user_permission_overrides_without_affecting_role_or_other_users(): void
    {
        $admin = $this->createAdmin();

        /** @var \App\Models\User $targetA */
        $targetA = User::factory()->create([
            'email' => 'target-a@example.test',
        ]);
        $targetA->assignRole('staff');

        /** @var \App\Models\User $targetB */
        $targetB = User::factory()->create([
            'email' => 'target-b@example.test',
        ]);
        $targetB->assignRole('staff');

        $updateResponse = $this->actingAs($admin)->put(route('admin.security.users.update', [
            'locale' => 'en',
            'user' => $targetA->id,
        ]), [
            'name' => $targetA->name,
            'email' => $targetA->email,
            'password' => '',
            'password_confirmation' => '',
            'roles' => ['staff'],
            'direct_permissions' => ['customers.create'],
            'denied_permissions' => ['customers.view'],
        ]);

        $updateResponse->assertRedirect(route('admin.security.users.index', ['locale' => 'en']));
        $updateResponse->assertSessionHas('success');

        $targetA = $targetA->fresh(['roles', 'permissions', 'deniedPermissions']);
        $targetB = $targetB->fresh(['roles', 'permissions', 'deniedPermissions']);

        $this->assertTrue($targetA->hasRole('staff'));
        $this->assertTrue($targetA->hasDirectPermission('customers.create'));
        $this->assertTrue($targetA->hasDeniedPermission('customers.view'));

        $this->assertTrue($targetB->hasRole('staff'));
        $this->assertFalse($targetB->hasDirectPermission('customers.create'));
        $this->assertFalse($targetB->hasDeniedPermission('customers.view'));

        $this->actingAs($targetA)
            ->get(route('admin.sales.customers.index', ['locale' => 'en']))
            ->assertForbidden();
        $this->actingAs($targetA)
            ->get(route('admin.sales.customers.create', ['locale' => 'en']))
            ->assertOk();

        $this->actingAs($targetB)
            ->get(route('admin.sales.customers.index', ['locale' => 'en']))
            ->assertOk();
        $this->actingAs($targetB)
            ->get(route('admin.sales.customers.create', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_cannot_grant_and_block_same_permission_for_same_user(): void
    {
        $admin = $this->createAdmin();
        $target = User::factory()->create([
            'email' => 'target-conflict@example.test',
        ]);
        $target->assignRole('staff');

        $response = $this->actingAs($admin)
            ->from(route('admin.security.users.edit', ['locale' => 'en', 'user' => $target->id]))
            ->put(route('admin.security.users.update', [
                'locale' => 'en',
                'user' => $target->id,
            ]), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
                'password_confirmation' => '',
                'roles' => ['staff'],
                'direct_permissions' => ['orders.view'],
                'denied_permissions' => ['orders.view'],
            ]);

        $response->assertRedirect(route('admin.security.users.edit', ['locale' => 'en', 'user' => $target->id]));
        $response->assertSessionHasErrors('denied_permissions');

        $this->assertFalse($target->fresh()->hasDirectPermission('orders.view'));
    }
}
