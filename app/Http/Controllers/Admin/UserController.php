<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(User::query()->count()),
                'label' => __('Total Users'),
                'icon' => 'bx-group',
            ],
            [
                'value' => number_format((int) DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->distinct()
                    ->count('model_id')),
                'label' => __('Users With Roles'),
                'icon' => 'bx-user-check',
            ],
            [
                'value' => number_format(Role::query()->count()),
                'label' => __('Available Roles'),
                'icon' => 'bx-shield-quarter',
            ],
            [
                'value' => number_format(User::query()->whereDate('created_at', '>=', now()->startOfMonth())->count()),
                'label' => __('New This Month'),
                'icon' => 'bx-user-plus',
            ],
        ];

        return view('admin.users.index', compact('users', 'tableStats'));
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'permissions', 'deniedPermissions']);

        $activityLogs = ActivityLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->take(12)
            ->get();

        $rolePermissionNames = $user->getPermissionsViaRoles()
            ->pluck('name')
            ->map(fn ($permission): string => (string) $permission)
            ->values()
            ->all();
        $directPermissionNames = $user->permissions
            ->pluck('name')
            ->map(fn ($permission): string => (string) $permission)
            ->values()
            ->all();
        $deniedPermissionNames = $user->deniedPermissions
            ->pluck('name')
            ->map(fn ($permission): string => (string) $permission)
            ->values()
            ->all();
        $effectivePermissionNames = collect($user->getAllPermissions()->pluck('name'))
            ->map(fn ($permission): string => (string) $permission)
            ->reject(fn (string $permission): bool => in_array($permission, $deniedPermissionNames, true))
            ->values()
            ->all();

        $recentNotifications = $user->notifications()
            ->latest()
            ->take(8)
            ->get();

        return view('admin.users.show', compact(
            'user',
            'activityLogs',
            'recentNotifications',
            'rolePermissionNames',
            'directPermissionNames',
            'deniedPermissionNames',
            'effectivePermissionNames'
        ));
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);
        $permissionGroups = $this->groupPermissions($permissions);

        return view('admin.security.users.create', compact('roles', 'permissionGroups'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = collect($request->input('roles', []))
            ->map(fn (mixed $role): string => (string) $role)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $directPermissions = $this->normalizePermissionInput($request->input('direct_permissions', []));
        $deniedPermissions = $this->normalizePermissionInput($request->input('denied_permissions', []));

        $this->ensureNoPermissionOverrideConflict($directPermissions, $deniedPermissions);
        unset($data['roles'], $data['direct_permissions'], $data['denied_permissions']);

        $user = User::query()->create($data);

        $user->syncRoles($roles);
        $user->syncPermissions($directPermissions);
        $this->syncDeniedPermissions($user, $deniedPermissions);
        $this->forgetPermissionCache();

        ActivityLogger::log('created', 'user', (int) $user->id, [
            'email' => $user->email,
            'roles' => $roles,
            'direct_permissions' => $directPermissions,
            'denied_permissions' => $deniedPermissions,
        ]);

        return redirect()
            ->route('admin.security.users.index')
            ->with('success', __('User created successfully.'));
    }

    public function edit(User $user): View
    {
        $user->loadMissing(['roles', 'permissions', 'deniedPermissions']);

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $permissions = Permission::query()->orderBy('name')->get(['id', 'name']);
        $permissionGroups = $this->groupPermissions($permissions);
        $currentRoles = $user->roles->pluck('name')->map(fn ($role) => (string) $role)->all();
        $directPermissionNames = $user->permissions->pluck('name')->map(fn ($permission) => (string) $permission)->all();
        $deniedPermissionNames = $user->deniedPermissions->pluck('name')->map(fn ($permission) => (string) $permission)->all();
        $rolePermissionNames = $user->getPermissionsViaRoles()->pluck('name')->map(fn ($permission) => (string) $permission)->all();
        $effectivePermissionNames = collect($user->getAllPermissions()->pluck('name'))
            ->map(fn ($permission) => (string) $permission)
            ->reject(fn (string $permission) => in_array($permission, $deniedPermissionNames, true))
            ->values()
            ->all();

        return view('admin.security.users.edit', compact(
            'user',
            'roles',
            'permissionGroups',
            'currentRoles',
            'directPermissionNames',
            'deniedPermissionNames',
            'rolePermissionNames',
            'effectivePermissionNames'
        ));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roles = collect($request->input('roles', []))
            ->map(fn (mixed $role): string => (string) $role)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $directPermissions = $this->normalizePermissionInput($request->input('direct_permissions', []));
        $deniedPermissions = $this->normalizePermissionInput($request->input('denied_permissions', []));

        $this->ensureNoPermissionOverrideConflict($directPermissions, $deniedPermissions);
        unset($data['roles'], $data['direct_permissions'], $data['denied_permissions']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles($roles);
        $user->syncPermissions($directPermissions);
        $this->syncDeniedPermissions($user, $deniedPermissions);
        $this->forgetPermissionCache();

        ActivityLogger::log('updated', 'user', (int) $user->id, [
            'email' => $user->email,
            'roles' => $roles,
            'direct_permissions' => $directPermissions,
            'denied_permissions' => $deniedPermissions,
        ]);

        return redirect()
            ->route('admin.security.users.index')
            ->with('success', __('User updated successfully.'));
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        ActivityLogger::log('updated', 'user', (int) $user->id, [
            'email' => $user->email,
            'section' => 'security_password',
        ]);

        return redirect()
            ->route('admin.users.show', ['locale' => app()->getLocale(), 'user' => $user])
            ->with('success', __('User password updated successfully.'))
            ->with('active_tab', 'security');
    }

    /**
     * @return array<int, string>
     */
    private function normalizePermissionInput(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        return collect($input)
            ->map(fn (mixed $permission): string => (string) $permission)
            ->map(fn (string $permission): string => trim($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $directPermissions
     * @param array<int, string> $deniedPermissions
     */
    private function ensureNoPermissionOverrideConflict(array $directPermissions, array $deniedPermissions): void
    {
        $conflict = array_values(array_intersect($directPermissions, $deniedPermissions));
        if ($conflict === []) {
            return;
        }

        throw ValidationException::withMessages([
            'denied_permissions' => __(
                'Permission :permission cannot be granted and denied at the same time.',
                ['permission' => $conflict[0]]
            ),
        ]);
    }

    /**
     * @param array<int, string> $permissionNames
     */
    private function syncDeniedPermissions(User $user, array $permissionNames): void
    {
        if ($permissionNames === []) {
            $user->deniedPermissions()->detach();
            return;
        }

        $permissionIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $user->deniedPermissions()->sync($permissionIds);
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
}
