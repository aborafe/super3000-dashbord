@extends('layouts.admin')

@section('title', __('Orders'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Orders') }}</h4>
            <p class="text-muted mb-0">{{ __('Track and manage customer orders.') }}</p>
        </div>

        @can('orders.create')
            <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>{{ __('Create order') }}
            </a>
        @endcan
    </div>

    <div class="card mb-4">
        <h5 class="card-header">{{ __('Filters') }}</h5>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.index') }}">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach ([
                                \App\Models\Order::STATUS_PENDING => __('Pending'),
                                \App\Models\Order::STATUS_CONFIRMED => __('Confirmed'),
                                \App\Models\Order::STATUS_SHIPPED => __('Shipped'),
                                \App\Models\Order::STATUS_COMPLETED => __('Completed'),
                                \App\Models\Order::STATUS_CANCELED => __('Canceled'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Payment status') }}</label>
                        <select name="payment_status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach ([
                                \App\Models\Order::PAYMENT_UNPAID => __('Unpaid'),
                                \App\Models\Order::PAYMENT_PARTIAL => __('Partial'),
                                \App\Models\Order::PAYMENT_PAID => __('Paid'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['payment_status'] ?? null) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Customer') }}</label>
                        <select name="partner_id" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($partners as $partner)
                                <option value="{{ $partner->id }}" @selected(($filters['partner_id'] ?? null) == $partner->id)>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-1">
                        <label class="form-label">{{ __('From') }}</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                    </div>

                    <div class="col-6 col-md-1">
                        <label class="form-label">{{ __('To') }}</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                    </div>

                    <div class="col-12 col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter me-1"></i>{{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Orders') }}</h5>
            <span class="text-muted small">{{ __('Total: :count', ['count' => $orders->total()]) }}</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Order #') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Profit') }}</th>
                        <th>{{ __('Created at') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="text-muted text-uppercase small">{{ $order->order_no }}</td>
                            <td>{{ $order->partner?->name ?? '—' }}</td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-label-warning' => $order->status === \App\Models\Order::STATUS_PENDING,
                                    'bg-label-primary' => $order->status === \App\Models\Order::STATUS_CONFIRMED,
                                    'bg-label-info' => $order->status === \App\Models\Order::STATUS_SHIPPED,
                                    'bg-label-success' => $order->status === \App\Models\Order::STATUS_COMPLETED,
                                    'bg-label-danger' => $order->status === \App\Models\Order::STATUS_CANCELED,
                                ])>
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-label-danger' => $order->payment_status === \App\Models\Order::PAYMENT_UNPAID,
                                    'bg-label-warning' => $order->payment_status === \App\Models\Order::PAYMENT_PARTIAL,
                                    'bg-label-success' => $order->payment_status === \App\Models\Order::PAYMENT_PAID,
                                ])>
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>
                            <td>{{ number_format($order->total, 2) }}</td>
                            <td>{{ number_format($order->profit, 2) }}</td>
                            <td class="text-muted small">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-icon btn-outline-primary">
                                    <i class="bx bx-show"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No orders found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
