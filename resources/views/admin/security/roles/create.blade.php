@extends('layouts.admin')

@section('title', __('Create Role'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Create Role') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.security.roles.index') }}">{{ __('Roles') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.security.roles.store') }}">
          @csrf

          <div class="mb-3">
            <label class="form-label">{{ __('Role Name') }}</label>
            <input
              type="text"
              name="name"
              value="{{ old('name') }}"
              class="form-control @error('name') is-invalid @enderror"
              placeholder="{{ __('Example: support_agent') }}">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          @include('admin.security.roles._permission-matrix', [
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => old('permissions', []),
          ])

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            <a href="{{ route('admin.security.roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

