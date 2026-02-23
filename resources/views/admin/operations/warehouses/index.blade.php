@extends('layouts.admin')

@section('title', __('Warehouses'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Warehouses') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Warehouses') }}</li>
          </ol>
        </nav>
      </div>
      <a href="{{ route('admin.operations.warehouses.create') }}" class="btn btn-primary">{{ __('Add Warehouse') }}</a>
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
                <th>{{ __('Location') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($warehouses as $warehouse)
                <tr>
                  <td>
                    <a href="{{ route('admin.operations.warehouses.edit', $warehouse) }}" class="text-body fw-medium">{{ $warehouse->name }}</a>
                  </td>
                  <td>
                    <a href="{{ route('admin.operations.warehouses.edit', $warehouse) }}" class="text-body">{{ $warehouse->location ?? '-' }}</a>
                  </td>
                  <td>
                    <span class="badge {{ $warehouse->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ $warehouse->is_active ? __('Active') : __('Inactive') }}
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="{{ route('admin.operations.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                    <form action="{{ route('admin.operations.warehouses.destroy', $warehouse) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Delete this warehouse?') }}')">{{ __('Delete') }}</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted">{{ __('No warehouses found.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $warehouses->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection
