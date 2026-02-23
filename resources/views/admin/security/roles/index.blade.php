@extends('layouts.admin')

@section('title', __('Roles & Permissions'))

@section('content')
  <style>
    .roles-toolbar .btn {
      min-height: 2.5rem;
      padding-inline: 1rem;
      font-weight: 600;
    }

    .roles-action-group {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      justify-content: flex-end;
      flex-wrap: nowrap;
    }

    .roles-action-group form {
      margin: 0;
    }

    .roles-action-group .btn {
      min-width: 90px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .35rem;
      white-space: nowrap;
    }

    .role-name-link {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      text-decoration: none;
    }
  </style>

  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 roles-toolbar gap-2">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Roles & Permissions') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Roles') }}</li>
          </ol>
        </nav>
      </div>
      @can('roles.create')
        <a href="{{ route('admin.security.roles.create') }}" class="btn btn-primary">
          <i class="icon-base bx bx-plus"></i>
          <span>{{ __('Create Role') }}</span>
        </a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Permissions') }}</th>
                <th>{{ __('Assigned Users') }}</th>
                <th class="text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $role)
                @php $isProtected = in_array(strtolower($role->name), $protectedRoles, true); @endphp
                <tr>
                  <td>
                    <a href="{{ route('admin.security.roles.edit', $role) }}" class="text-body fw-medium role-name-link">
                      <i class="bx bx-shield-quarter text-primary"></i>
                      <span>{{ __(ucfirst($role->name)) }}</span>
                    </a>
                    @if ($isProtected)
                      <span class="badge bg-label-warning ms-2">{{ __('Protected') }}</span>
                    @endif
                  </td>
                  <td>
                    <a href="{{ route('admin.security.roles.edit', $role) }}" class="text-body">{{ $role->permissions_count }}</a>
                  </td>
                  <td>{{ (int) $role->users_count }}</td>
                  <td class="text-end">
                    <div class="roles-action-group">
                      <a href="{{ route('admin.security.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-edit-alt"></i>
                        <span>{{ __('Edit') }}</span>
                      </a>

                      @can('roles.delete')
                        <form method="POST" action="{{ route('admin.security.roles.destroy', $role) }}" onsubmit="return confirm(@js(__('Delete this role?')));">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                            class="btn btn-sm btn-outline-danger"
                            @disabled($isProtected || (int) $role->users_count > 0)
                            title="{{ $isProtected ? __('This role is protected and cannot be deleted.') : ((int) $role->users_count > 0 ? __('Cannot delete role assigned to users.') : __('Delete')) }}">
                            <i class="bx bx-trash"></i>
                            <span>{{ __('Delete') }}</span>
                          </button>
                        </form>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted">{{ __('No roles found.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $roles->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection

