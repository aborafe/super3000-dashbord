<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Protected roles cannot be deleted from UI.
     *
     * @var array<int, string>
     */
    private array $protectedRoles = ['admin'];

    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Role::query()->count()),
                'label' => __('Total Roles'),
                'icon' => 'bx-shield-quarter',
            ],
            [
                'value' => number_format(Permission::query()->count()),
                'label' => __('Total Permissions'),
                'icon' => 'bx-lock-open-alt',
            ],
            [
                'value' => number_format(Role::query()->has('permissions')->count()),
                'label' => __('Roles With Permissions'),
                'icon' => 'bx-check-shield',
            ],
            [
                'value' => number_format((int) DB::table('model_has_roles')->distinct()->count('model_id')),
                'label' => __('Assigned Users'),
                'icon' => 'bx-user-check',
            ],
        ];

        $protectedRoles = $this->protectedRoles;

        return view('admin.security.roles.index', compact('roles', 'tableStats', 'protectedRoles'));
    }

    public function create(): View
    {
        $permissions = Permission::query()->orderBy('name')->get();
        $permissionGroups = $this->groupPermissions($permissions);

        return view('admin.security.roles.create', compact('permissionGroups'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $permissions = collect($validated['permissions'] ?? [])
            ->map(fn (mixed $permission): string => (string) $permission)
            ->filter()
            ->unique()
            ->values()
            ->all();

        /** @var \Spatie\Permission\Models\Role $role */
        $role = Role::query()->create([
            'name' => (string) $validated['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($permissions);
        $this->forgetPermissionCache();

        ActivityLogger::log('created', 'role', (int) $role->id, [
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ]);

        return redirect()
            ->route('admin.security.roles.index')
            ->with('success', __('Role created successfully.'));
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get();
        $permissionGroups = $this->groupPermissions($permissions);
        $rolePermissions = $role->permissions->pluck('name')->all();

        return view('admin.security.roles.edit', compact('role', 'permissionGroups', 'rolePermissions'));
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $permissions = collect($request->input('permissions', []))
            ->map(fn (mixed $permission): string => (string) $permission)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $role->syncPermissions($permissions);
        $this->forgetPermissionCache();

        ActivityLogger::log('updated', 'role', (int) $role->id, [
            'name' => $role->name,
            'permissions_count' => count($permissions),
        ]);

        return redirect()
            ->route('admin.security.roles.index')
            ->with('success', __('Role permissions updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtectedRole($role->name)) {
            return back()->with('error', __('This role is protected and cannot be deleted.'));
        }

        if ($role->users()->exists()) {
            return back()->with('error', __('Cannot delete role assigned to users.'));
        }

        $roleId = (int) $role->id;
        $roleName = (string) $role->name;
        $role->delete();
        $this->forgetPermissionCache();

        ActivityLogger::log('deleted', 'role', $roleId, ['name' => $roleName]);

        return redirect()
            ->route('admin.security.roles.index')
            ->with('success', __('Role deleted successfully.'));
    }

    private function forgetPermissionCache(): void
    {
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * @return array<int, array{key: string, label: string, permissions: \Illuminate\Database\Eloquent\Collection<int, Permission>}>
     */
    private function groupPermissions(Collection $permissions): array
    {
        return $permissions
            ->groupBy(function (Permission $permission): string {
                $segment = Str::before((string) $permission->name, '.');

                return $segment !== '' ? $segment : 'general';
            })
            ->map(function (Collection $group, string $key): array {
                return [
                    'key' => $key,
                    'label' => Str::headline($key),
                    'permissions' => $group->values(),
                ];
            })
            ->values()
            ->all();
    }

    private function isProtectedRole(string $roleName): bool
    {
        return in_array(Str::lower($roleName), $this->protectedRoles, true);
    }
}
