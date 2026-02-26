@extends('layouts.admin')

@section('title', __('Inventory'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Inventory Movements') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Inventory') }}</li>
          </ol>
        </nav>
      </div>
      <a href="{{ route('admin.catalog.inventory.create') }}" class="btn btn-primary">{{ __('Add Movement') }}</a>
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
                <th>{{ __('Product') }}</th>
                <th>{{ __('Warehouse') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Qty') }}</th>
                <th>{{ __('Note') }}</th>
                <th>{{ __('Date') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($movements as $movement)
                <tr>
                  <td>
                    @if($movement->product)
                      <a href="{{ route('admin.products.edit', $movement->product) }}" class="text-body fw-medium">{{ $movement->product->name }}</a>
                    @else
                      <span>-</span>
                    @endif
                  </td>
                  <td>
                    @if($movement->warehouse)
                      <a href="{{ route('admin.operations.warehouses.edit', $movement->warehouse) }}" class="text-body">{{ $movement->warehouse->name }}</a>
                    @else
                      <span>-</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge {{ $movement->type === 'in' ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ $movement->type === 'in' ? __('In') : __('Out') }}
                    </span>
                  </td>
                  <td>{{ $movement->qty }}</td>
                  <td>{{ $movement->note ?? '-' }}</td>
                  <td>{{ $movement->created_at?->format('Y-m-d') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted">{{ __('No inventory movements.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $movements->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection
