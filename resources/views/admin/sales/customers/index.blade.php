@extends('layouts.admin')

@section('title', __('Customers'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Customers') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Customers') }}</li>
          </ol>
        </nav>
      </div>
      <a href="{{ route('admin.sales.customers.create') }}" class="btn btn-primary">{{ __('Add Customer') }}</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-header">
        <form method="GET" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">{{ __('Search') }}</label>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('Customer name') }}">
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
            <a href="{{ route('admin.sales.customers.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
          </div>
        </form>
      </div>
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Email') }}</th>
                <th>{{ __('Phone') }}</th>
                <th>{{ __('WhatsApp') }}</th>
                <th>{{ __('City') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="text-end">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($customers as $customer)
                <tr>
                  <td>
                    <a href="{{ route('admin.sales.customers.edit', $customer) }}" class="text-body fw-medium">{{ $customer->name }}</a>
                  </td>
                  <td>{{ $customer->email ?? '-' }}</td>
                  <td>{{ $customer->phone ?? '-' }}</td>
                  <td>{{ $customer->whatsapp ?? '-' }}</td>
                  <td>{{ $customer->city ?? '-' }}</td>
                  <td>
                    <span class="badge {{ $customer->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                      {{ $customer->is_active ? __('Active') : __('Inactive') }}
                    </span>
                  </td>
                  <td class="text-end">
                    <a href="{{ route('admin.sales.customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                    <form action="{{ route('admin.sales.customers.destroy', $customer) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Delete this customer?') }}')">{{ __('Delete') }}</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">{{ __('No customers found.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $customers->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection
