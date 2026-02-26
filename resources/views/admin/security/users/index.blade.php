@extends('layouts.admin')

@section('title', __('Users'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Users') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Users') }}</li>
          </ol>
        </nav>
      </div>
      <a href="{{ route('admin.security.users.create') }}" class="btn btn-primary">{{ __('Add User') }}</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Role') }}</th>
                <th>{{ __('Created') }}</th>
                <th class="text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
                <tr>
                  <td>
                    <a href="{{ route('admin.security.users.edit', $user) }}" class="text-body fw-medium">{{ $user->name }}</a>
                  </td>
                  <td>
                    <a href="{{ route('admin.security.users.edit', $user) }}" class="text-body">{{ $user->email }}</a>
                  </td>
                  <td>{{ $user->roles->first()?->name ?? '-' }}</td>
                  <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                  <td class="text-end">
                    <a href="{{ route('admin.security.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">{{ __('No users found.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $users->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection
