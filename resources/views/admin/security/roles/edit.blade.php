@extends('layouts.admin')

@section('title', __('Edit Role'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Edit Role') }}: {{ __(ucfirst($role->name)) }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.security.roles.index') }}">{{ __('Roles') }}</a></li>
            <li class="breadcrumb-item active">{{ __(ucfirst($role->name)) }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.security.roles.update', $role) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label">{{ __('Role Name') }}</label>
            <input type="text" class="form-control" value="{{ $role->name }}" readonly>
            <small class="text-muted">{{ __('Role name is fixed. Edit permission matrix below.') }}</small>
          </div>

          @include('admin.security.roles._permission-matrix', [
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $rolePermissions,
          ])

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('admin.security.roles.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
