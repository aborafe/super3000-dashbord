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

        @include('admin.components.table-stats-strip')

        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-3">
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
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.sales.orders.index') }}"
                            class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Order No') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
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
                                    <td>{{ $order->order_no }}</td>
                                    <td>{{ $order->customer?->name }}</td>
                                    <td><span class="badge {{ $badge }}">{{ __(ucfirst($normalizedStatus)) }}</span>
                                    </td>
                                    <td>{{ money($order->total, 2) }}</td>
                                    <td>{{ $order->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.sales.orders.show', $order) }}"
                                            class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('No orders found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $orders->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

