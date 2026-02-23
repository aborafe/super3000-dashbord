<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles {
        HasRoles::hasPermissionTo as protected hasPermissionToFromRoles;
    }

    /**
     * Cache resolved denied permissions names for the current request lifecycle.
     *
     * @var array<int, string>|null
     */
    private ?array $resolvedDeniedPermissionNames = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.'.$this->getKey();
    }

    public function deniedPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_denied_permissions', 'user_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * @return array<int, string>
     */
    public function deniedPermissionNames(): array
    {
        if ($this->resolvedDeniedPermissionNames !== null) {
            return $this->resolvedDeniedPermissionNames;
        }

        $names = $this->relationLoaded('deniedPermissions')
            ? $this->deniedPermissions->pluck('name')->map(fn ($name) => (string) $name)->values()->all()
            : $this->deniedPermissions()->pluck('permissions.name')->map(fn ($name) => (string) $name)->values()->all();

        $this->resolvedDeniedPermissionNames = $names;

        return $names;
    }

    public function hasDeniedPermission(string $permissionName): bool
    {
        $permissionName = trim($permissionName);
        if ($permissionName === '') {
            return false;
        }

        return in_array($permissionName, $this->deniedPermissionNames(), true);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $resolvedName = $this->resolvePermissionName($permission);
        if ($resolvedName !== '' && $this->hasDeniedPermission($resolvedName)) {
            return false;
        }

        return $this->hasPermissionToFromRoles($permission, $guardName);
    }

    private function resolvePermissionName(mixed $permission): string
    {
        if ($permission instanceof PermissionContract) {
            return (string) $permission->name;
        }

        if (is_string($permission)) {
            $trimmed = trim($permission);
            if ($trimmed === '') {
                return '';
            }

            if (ctype_digit($trimmed)) {
                return (string) (Permission::query()->find((int) $trimmed)?->name ?? '');
            }

            return $trimmed;
        }

        if (is_int($permission)) {
            return (string) (Permission::query()->find($permission)?->name ?? '');
        }

        return '';
    }
}
