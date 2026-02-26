@extends('layouts.admin')

@section('title', __('Invoice List'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Invoice List') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Invoice List') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-4">{{ __('Filter') }}</h5>
        <form method="GET" id="invoices-filter-form" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select">
              <option value="">{{ __('All') }}</option>
              <option value="pending" @selected($status === 'pending')>{{ __('Pending') }}</option>
              <option value="approved" @selected($status === 'approved')>{{ __('Approved') }}</option>
              <option value="shipped" @selected($status === 'shipped')>{{ __('Shipped') }}</option>
              <option value="delivered" @selected($status === 'delivered')>{{ __('Delivered') }}</option>
              <option value="cancelled" @selected($status === 'cancelled')>{{ __('Cancelled') }}</option>
              <option value="returned" @selected($status === 'returned')>{{ __('Returned') }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('From') }}</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control" />
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('To') }}</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control" />
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Reset') }}</a>
          </div>
        </form>
      </div>
      <div class="card-body border-top">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-2">
            <input
              id="invoices-search"
              type="text"
              name="q"
              value="{{ $search }}"
              form="invoices-filter-form"
              class="form-control form-control-sm"
              placeholder="{{ __('Search') }}"
              aria-label="{{ __('Search') }}" />
          </div>
          <div class="d-flex align-items-center gap-2">
            <select name="per_page" class="form-select form-select-sm w-auto" form="invoices-filter-form">
              <option value="10" @selected($perPage === 10)>10</option>
              <option value="25" @selected($perPage === 25)>25</option>
              <option value="50" @selected($perPage === 50)>50</option>
            </select>
            <div class="btn-group">
              <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="icon-base bx bx-export me-1"></i>{{ __('Export') }}
              </button>
              <div class="dropdown-menu">
                <a class="dropdown-item" data-no-loader="1"
                  href="{{ route('admin.invoices.export', array_merge(['locale' => app()->getLocale(), 'format' => 'csv'], request()->query())) }}">{{ __('CSV') }}</a>
                <a class="dropdown-item" data-no-loader="1"
                  href="{{ route('admin.invoices.export', array_merge(['locale' => app()->getLocale(), 'format' => 'excel'], request()->query())) }}">{{ __('Excel') }}</a>
                <a class="dropdown-item" target="_blank" data-no-loader="1"
                  href="{{ route('admin.invoices.export', array_merge(['locale' => app()->getLocale(), 'format' => 'pdf'], request()->query())) }}">{{ __('PDF') }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Invoice') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Discount') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($invoices as $order)
                @php
                  $normalizedStatus = $order->normalized_status;
                  $badge = match($normalizedStatus) {
                      'approved' => 'bg-label-success',
                      'shipped' => 'bg-label-info',
                      'delivered' => 'bg-label-primary',
                      'returned' => 'bg-label-secondary',
                      'cancelled' => 'bg-label-danger',
                      default => 'bg-label-warning',
                  };
                @endphp
                <tr>
                  <td>
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-body fw-medium">{{ $order->order_no }}</a>
                  </td>
                  <td>
                    @if($order->customer)
                      <a href="{{ route('admin.sales.customers.edit', $order->customer) }}" class="text-body">{{ $order->customer->name }}</a>
                    @else
                      <span>-</span>
                    @endif
                  </td>
                  <td>{{ money($order->total, 2) }}</td>
                  <td class="{{ (float) $order->items_discount_total > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                    {{ (float) $order->items_discount_total > 0 ? money($order->items_discount_total, 2) : '' }}
                  </td>
                  <td><span class="badge {{ $badge }}">{{ __(ucfirst($normalizedStatus)) }}</span></td>
                  <td>{{ $order->created_at?->format('Y-m-d') }}</td>
                  <td>
                    <div class="d-inline-flex align-items-center gap-1">
                      <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary btn-icon-soft">
                        <i class="icon-base bx bx-show"></i>
                      </a>
                      <a href="{{ route('admin.invoices.print', $order) }}" class="btn btn-sm btn-outline-primary btn-icon-soft">
                        <i class="icon-base bx bx-printer"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">{{ __('No data.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
      </div>
      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
        <small class="text-muted">
          {{ __('Showing') }} {{ $invoices->firstItem() ?? 0 }} {{ __('to') }} {{ $invoices->lastItem() ?? 0 }}
          {{ __('of') }} {{ $invoices->total() }} {{ __('results') }}
        </small>
        {{ $invoices->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
@endsection

