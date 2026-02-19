@extends('layouts.admin')

@section('title', __('Orders'))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Orders') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Orders') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">{{ __('Filter') }}</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ $search }}" class="form-control"
                            placeholder="{{ __('Order No') }}" />
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.orders.index') }}"
                            class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>
        </div>

        @include('admin.components.table-stats-strip')

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="d-flex align-items-center">
                        {{ __('Showing') }}
                        <select name="per_page" class="form-select form-select-sm mx-2" form="orders-table-form">
                            <option value="10" @selected($perPage === 10)>10</option>
                            <option value="25" @selected($perPage === 25)>25</option>
                            <option value="50" @selected($perPage === 50)>50</option>
                        </select>
                        {{ __('results') }}
                    </label>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <form id="orders-table-form" method="GET">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Order No') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Paid') }}</th>
                                <th>{{ __('Due') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $normalizedStatus = $order->normalized_status;
                                    $badge = match ($normalizedStatus) {
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
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="text-body fw-medium">{{ $order->order_no }}</a>
                                    </td>
                                    <td>
                                        @if ($order->customer)
                                            <a href="{{ route('admin.sales.customers.edit', $order->customer) }}"
                                                class="text-body">{{ $order->customer->name }}</a>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $badge }}">{{ __(ucfirst($normalizedStatus)) }}</span></td>
                                    <td>${{ number_format($order->paid_amount, 2) }}</td>
                                    <td>${{ number_format($order->due_amount, 2) }}</td>
                                    <td>${{ number_format($order->total, 2) }}</td>
                                    <td>{{ $order->created_at?->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="icon-base bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">{{ __('No orders found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <small class="text-muted">
                    {{ __('Showing') }} {{ $orders->firstItem() ?? 0 }} {{ __('to') }}
                    {{ $orders->lastItem() ?? 0 }}
                    {{ __('of') }} {{ $orders->total() }} {{ __('results') }}
                </small>
                {{ $orders->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
