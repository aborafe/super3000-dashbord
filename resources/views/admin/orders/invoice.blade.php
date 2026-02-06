@extends('layouts.admin')

@section('title', __('Invoice'))

@section('content')
    <style>
        @media print {
            .layout-menu,
            .layout-navbar,
            .layout-overlay,
            .no-print {
                display: none !important;
            }

            .content-wrapper,
            .container-xxl {
                padding: 0 !important;
                margin: 0 !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="no-print d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="mb-0">{{ __('Invoice') }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">
                {{ __('Back to order') }}
            </a>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
                {{ __('Print') }}
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <div class="h5 mb-1">{{ config('app.name', 'Super3000') }}</div>
                    <div class="text-muted">{{ __('Invoice') }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ $order->order_no }}</div>
                    <div class="text-muted small">{{ $order->created_at?->format('Y-m-d') }}</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('Billed to') }}</div>
                    <div class="fw-semibold">{{ $order->partner?->name ?? '—' }}</div>
                    <div class="text-muted">{{ $order->partner?->phone ?? '' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">{{ __('Status') }}</div>
                    <div class="fw-semibold">{{ ucfirst($order->status) }}</div>
                    <div class="text-muted">{{ __('Payment: :status', ['status' => ucfirst($order->payment_status)]) }}</div>
                </div>
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

            <div class="d-flex justify-content-end mt-4">
                <div class="text-end">
                    <div class="text-muted small">{{ __('Total') }}</div>
                    <div class="h5 mb-0">{{ number_format($order->total, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
