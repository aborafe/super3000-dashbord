@php $selectedPermissions = collect($selectedPermissions ?? [])->map(fn ($value) => (string) $value)->all(); @endphp

<style>
    .permission-matrix-card .card-header .btn {
        min-width: 76px;
        font-weight: 600;
    }

    .permission-matrix-card .form-check-label {
        font-size: .88rem;
        line-height: 1.35;
    }
</style>

<div class="border rounded p-3 permission-matrix-card" data-permission-matrix>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h6 class="mb-0">{{ __('Permissions Matrix') }}</h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" data-permissions-toggle="all-on">
                {{ __('Select all') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-permissions-toggle="all-off">
                {{ __('Clear all') }}
            </button>
        </div>
    </div>

    <div class="row g-3">
        @foreach($permissionGroups as $group)
            <div class="col-lg-4 col-md-6" data-permission-group>
                    <div class="card h-100 border">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>{{ __($group['label']) }}</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-group-toggle>
                            {{ __('All') }}
                        </button>
                    </div>
                    <div class="card-body py-2">
                        @foreach($group['permissions'] as $permission)
                            <div class="form-check mb-2">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->name }}"
                                    id="perm-{{ $group['key'] }}-{{ $permission->id }}"
                                    @checked(in_array($permission->name, old('permissions', $selectedPermissions), true))>
                                <label class="form-check-label" for="perm-{{ $group['key'] }}-{{ $permission->id }}">
                                    {{ __($permission->name) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @error('permissions')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
    @error('permissions.*')
        <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const matrix = document.querySelector('[data-permission-matrix]');
        if (!matrix) {
            return;
        }

        const allCheckboxes = () => Array.from(matrix.querySelectorAll('input[name="permissions[]"]'));
        const setChecked = (nodes, checked) => nodes.forEach((node) => {
            node.checked = checked;
        });

        const allOn = matrix.querySelector('[data-permissions-toggle="all-on"]');
        const allOff = matrix.querySelector('[data-permissions-toggle="all-off"]');

        if (allOn) {
            allOn.addEventListener('click', function() {
                setChecked(allCheckboxes(), true);
            });
        }

        if (allOff) {
            allOff.addEventListener('click', function() {
                setChecked(allCheckboxes(), false);
            });
        }

        matrix.querySelectorAll('[data-group-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', function() {
                const card = toggle.closest('[data-permission-group]');
                if (!card) {
                    return;
                }

                const checkboxes = Array.from(card.querySelectorAll('input[name="permissions[]"]'));
                const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);
                setChecked(checkboxes, shouldCheck);
            });
        });
    });
</script>

