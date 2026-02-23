@php
    $selectedDirectPermissions = collect(old('direct_permissions', $selectedDirectPermissions ?? []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();

    $selectedDeniedPermissions = collect(old('denied_permissions', $selectedDeniedPermissions ?? []))
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();

    $rolePermissionNames = collect($rolePermissionNames ?? [])
        ->map(fn ($value) => (string) $value)
        ->values()
        ->all();
@endphp

<style>
    .user-overrides-shell {
        border: 1px solid rgba(67, 89, 113, .18);
        border-radius: .9rem;
        background: linear-gradient(180deg, rgba(13, 110, 253, .03), rgba(255, 255, 255, .9));
    }

    .override-row {
        border-bottom: 1px dashed rgba(67, 89, 113, .15);
        padding: .55rem .1rem;
    }

    .override-row:last-child {
        border-bottom: 0;
    }

    .override-switch {
        min-width: 74px;
    }

    .override-toolbar .btn {
        min-width: 130px;
    }

    @media (max-width: 767.98px) {
        .override-switch {
            min-width: 64px;
        }
    }
</style>

<div class="user-overrides-shell p-3" data-user-permission-overrides>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 override-toolbar">
        <div>
            <h6 class="mb-1">{{ __('Permission Overrides') }}</h6>
            <small class="text-muted">{{ __('Grant or block permissions for this user only (role itself stays unchanged).') }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-user-overrides-toggle="grant-all">
                <i class="bx bx-check-double me-1"></i>{{ __('Grant All') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-user-overrides-toggle="clear-all">
                <i class="bx bx-eraser me-1"></i>{{ __('Clear Overrides') }}
            </button>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($permissionGroups as $group)
            <div class="col-xl-6">
                <div class="card h-100 border">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between">
                        <strong>{{ __($group['label']) }}</strong>
                        <span class="badge bg-label-secondary">{{ count($group['permissions']) }}</span>
                    </div>
                    <div class="card-body py-2">
                        @foreach ($group['permissions'] as $permission)
                            @php $permissionName = (string) $permission->name; @endphp
                            @php $isFromRole = in_array($permissionName, $rolePermissionNames, true); @endphp
                            <div class="override-row d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div class="pe-2">
                                    <div class="fw-medium">{{ __($permissionName) }}</div>
                                    @if ($isFromRole)
                                        <small class="text-muted">{{ __('Inherited from role') }}</small>
                                    @endif
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0 override-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="grant-{{ $group['key'] }}-{{ $permission->id }}"
                                            name="direct_permissions[]"
                                            value="{{ $permissionName }}"
                                            data-override-grant="{{ $permissionName }}"
                                            @checked(in_array($permissionName, $selectedDirectPermissions, true))
                                        >
                                        <label class="form-check-label small" for="grant-{{ $group['key'] }}-{{ $permission->id }}">
                                            {{ __('Grant') }}
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-0 override-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="deny-{{ $group['key'] }}-{{ $permission->id }}"
                                            name="denied_permissions[]"
                                            value="{{ $permissionName }}"
                                            data-override-deny="{{ $permissionName }}"
                                            @checked(in_array($permissionName, $selectedDeniedPermissions, true))
                                        >
                                        <label class="form-check-label small text-danger" for="deny-{{ $group['key'] }}-{{ $permission->id }}">
                                            {{ __('Block') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @error('direct_permissions')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('direct_permissions.*')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('denied_permissions')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('denied_permissions.*')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shell = document.querySelector('[data-user-permission-overrides]');
        if (!shell) {
            return;
        }

        const grantInputs = () => Array.from(shell.querySelectorAll('input[name="direct_permissions[]"]'));
        const denyInputs = () => Array.from(shell.querySelectorAll('input[name="denied_permissions[]"]'));

        const mapByValue = (inputs) => {
            const map = new Map();
            inputs.forEach((input) => map.set(input.value, input));
            return map;
        };

        const syncMutualExclusion = () => {
            const grants = mapByValue(grantInputs());
            const denies = mapByValue(denyInputs());

            grants.forEach((grantInput, permissionName) => {
                const denyInput = denies.get(permissionName);
                if (!denyInput) {
                    return;
                }

                grantInput.addEventListener('change', function () {
                    if (grantInput.checked) {
                        denyInput.checked = false;
                    }
                });

                denyInput.addEventListener('change', function () {
                    if (denyInput.checked) {
                        grantInput.checked = false;
                    }
                });
            });
        };

        syncMutualExclusion();

        const grantAllButton = shell.querySelector('[data-user-overrides-toggle="grant-all"]');
        const clearAllButton = shell.querySelector('[data-user-overrides-toggle="clear-all"]');

        if (grantAllButton) {
            grantAllButton.addEventListener('click', function () {
                grantInputs().forEach((input) => {
                    input.checked = true;
                });
                denyInputs().forEach((input) => {
                    input.checked = false;
                });
            });
        }

        if (clearAllButton) {
            clearAllButton.addEventListener('click', function () {
                grantInputs().forEach((input) => {
                    input.checked = false;
                });
                denyInputs().forEach((input) => {
                    input.checked = false;
                });
            });
        }
    });
</script>

