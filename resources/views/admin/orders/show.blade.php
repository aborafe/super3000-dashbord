@extends('layouts.admin')

@section('title', __('Order details'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Order :no', ['no' => $order->order_no]) }}</h4>
            <p class="text-muted mb-0">
                {{ __('Customer: :name', ['name' => $order->partner?->name ?? '—']) }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
                {{ __('Back to list') }}
            </a>
            <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-outline-primary btn-sm">
                {{ __('Invoice') }}
            </a>
            @can('orders.update')
                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-primary btn-sm">
                    {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Order items') }}</h5>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Qty') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Line total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product?->name ?? '—' }}</td>
                                    <td>{{ $item->qty }}</td>
                                    <td>{{ number_format($item->price, 2) }}</td>
                                    <td>{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Summary') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">{{ __('Subtotal') }}</dt>
                        <dd class="col-6 text-end">{{ number_format($order->total, 2) }}</dd>
                        <dt class="col-6 text-muted">{{ __('Cost total') }}</dt>
                        <dd class="col-6 text-end">{{ number_format($order->cost_total, 2) }}</dd>
                        <dt class="col-6 text-muted">{{ __('Profit') }}</dt>
                        <dd class="col-6 text-end text-success fw-semibold">
                            {{ number_format($order->profit, 2) }}
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Status') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        {{ __('Change status and payment status for this order.') }}
                    </p>
                    @can('orders.change_status')
                        <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mb-3">
                            @csrf
                            @method('PUT')
                            <label class="form-label">{{ __('Order status') }}</label>
                            <select name="status" class="form-select mb-2">
                                @foreach ([
                                    \App\Models\Order::STATUS_PENDING => __('Pending'),
                                    \App\Models\Order::STATUS_CONFIRMED => __('Confirmed'),
                                    \App\Models\Order::STATUS_SHIPPED => __('Shipped'),
                                    \App\Models\Order::STATUS_COMPLETED => __('Completed'),
                                    \App\Models\Order::STATUS_CANCELED => __('Canceled'),
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($order->status === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                {{ __('Update status') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.orders.update-payment-status', $order) }}">
                            @csrf
                            @method('PUT')
                            <label class="form-label">{{ __('Payment status') }}</label>
                            <select name="payment_status" class="form-select mb-2">
                                @foreach ([
                                    \App\Models\Order::PAYMENT_UNPAID => __('Unpaid'),
                                    \App\Models\Order::PAYMENT_PARTIAL => __('Partial'),
                                    \App\Models\Order::PAYMENT_PAID => __('Paid'),
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($order->payment_status === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                {{ __('Update payment') }}
                            </button>
                        </form>
                    @else
                        <p class="text-muted small mb-0">
                            {{ __('You do not have permission to change order status.') }}
                        </p>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Payments') }}</h5>
                </div>
                <div class="card-body">
                    @if ($order->payments->isNotEmpty())
                        <ul class="list-group mb-3">
                            @foreach ($order->payments as $payment)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        {{ $payment->paid_at?->format('Y-m-d') ?? '—' }} · {{ $payment->method }}
                                    </span>
                                    <span class="fw-semibold">{{ number_format($payment->amount, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-3">{{ __('No payments recorded yet.') }}</p>
                    @endif

                    @can('orders.update')
                        <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">{{ __('Amount') }}</label>
                                <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}"
                                    class="form-control @error('amount') is-invalid @enderror" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ __('Method') }}</label>
                                <input type="text" name="method" value="{{ old('method', 'cash') }}" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Paid at') }}</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at') }}" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                {{ __('Add payment') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection
