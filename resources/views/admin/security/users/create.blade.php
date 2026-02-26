@extends('layouts.admin')

@section('title', __('Create User'))

@section('content')
    <style>
        .user-editor-card {
            border: 1px solid rgba(67, 89, 113, .15);
            box-shadow: 0 10px 28px rgba(34, 41, 47, .06);
        }

        .role-tile {
            border: 1px solid rgba(67, 89, 113, .2);
            border-radius: .65rem;
            padding: .7rem .8rem;
            transition: all .2s ease;
        }

        .role-tile:has(input:checked) {
            border-color: rgba(13, 110, 253, .55);
            background-color: rgba(13, 110, 253, .08);
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, .2);
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Create User') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.security.users.index') }}">{{ __('Users') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Create') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card user-editor-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.security.users.store') }}" class="row g-4">
                    @csrf

                    <div class="col-12">
                        <h5 class="mb-1">{{ __('Account Info') }}</h5>
                        <p class="text-muted mb-0">{{ __('Create login credentials and assign base roles first, then fine-tune permissions below.') }}</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Password') }}</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <label class="form-label mb-0">{{ __('Roles') }}</label>
                            <small class="text-muted">{{ __('Base permissions come from selected roles') }}</small>
                        </div>
                        <div class="border rounded p-3 @error('roles') border-danger @enderror">
                            <div class="row g-2">
                                @foreach ($roles as $role)
                                    <div class="col-md-4 col-sm-6">
                                        <label class="role-tile d-flex align-items-center gap-2 w-100">
                                            <input
                                                class="form-check-input m-0"
                                                type="checkbox"
                                                id="role-create-{{ $role->id }}"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                @checked(in_array($role->name, old('roles', []), true))
                                            >
                                            <span class="fw-medium">{{ __(ucfirst($role->name)) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('roles')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('roles.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        @include('admin.security.users._permissions-overrides', [
                            'permissionGroups' => $permissionGroups,
                            'selectedDirectPermissions' => [],
                            'selectedDeniedPermissions' => [],
                            'rolePermissionNames' => [],
                        ])
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.security.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>{{ __('Save User') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
