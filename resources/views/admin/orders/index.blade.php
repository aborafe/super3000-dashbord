@extends('layouts.admin')

@section('title', __('Orders'))

@section('content')
    @php
        $initialLatestOrderId = (int) ($orders->first()?->id ?? 0);
        $initialOrdersSignature = hash(
            'sha256',
            $orders
                ->getCollection()
                ->map(fn($order) => implode(':', [
                    (string) $order->id,
                    (string) optional($order->updated_at)->timestamp,
                    (string) $order->status,
                    (string) $order->total,
                ]))
                ->implode('|')
        );
    @endphp
    <style>
        .order-row-fresh {
            animation: order-row-fresh-pulse 2.2s ease;
            background-color: rgba(13, 110, 253, .08);
        }

        @keyframes order-row-fresh-pulse {
            0% {
                background-color: rgba(13, 110, 253, .22);
            }

            100% {
                background-color: rgba(13, 110, 253, .08);
            }
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #28c76f;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(40, 199, 111, .65);
            animation: live-dot-pulse 1.6s infinite;
        }

        @keyframes live-dot-pulse {
            70% {
                box-shadow: 0 0 0 9px rgba(40, 199, 111, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 199, 111, 0);
            }
        }
    </style>
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
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-success">
                        <span class="live-dot me-1"></span>{{ __('Refresh') }}
                    </span>
                    <span class="badge bg-label-primary d-none" data-live-new-count></span>
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
                                <th>{{ __('Discount') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody data-orders-table-body
                            data-live-endpoint="{{ route('admin.orders.live', ['locale' => app()->getLocale()]) }}"
                            data-latest-order-id="{{ $initialLatestOrderId }}"
                            data-signature="{{ $initialOrdersSignature }}"
                            data-current-per-page="{{ (int) $perPage }}">
                            @include('admin.orders.partials.table-rows', ['orders' => $orders])
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

@section('page-scripts')
    <script>
        (() => {
            const tableBody = document.querySelector('[data-orders-table-body]');
            if (!tableBody) {
                return;
            }

            const liveEndpoint = tableBody.dataset.liveEndpoint || '';
            if (liveEndpoint === '') {
                return;
            }

            const newCountBadge = document.querySelector('[data-live-new-count]');
            const knownOrderIds = new Set(
                Array.from(tableBody.querySelectorAll('tr[data-order-id]'))
                    .map((row) => Number.parseInt(row.getAttribute('data-order-id') || '0', 10))
                    .filter((id) => Number.isInteger(id) && id > 0)
            );
            let latestOrderId = Number.parseInt(tableBody.dataset.latestOrderId || '0', 10) || 0;
            let lastSignature = tableBody.dataset.signature || '';
            const pollingIntervalMs = 7000;
            const searchParams = new URLSearchParams(window.location.search);
            const currentPage = Number.parseInt(searchParams.get('page') || '1', 10) || 1;

            if (currentPage > 1) {
                return;
            }

            const updateNewCountBadge = (count) => {
                if (!newCountBadge) {
                    return;
                }

                const safeCount = Math.max(0, Number.parseInt(String(count), 10) || 0);
                if (safeCount === 0) {
                    newCountBadge.classList.add('d-none');
                    newCountBadge.textContent = '';
                    return;
                }

                newCountBadge.classList.remove('d-none');
                newCountBadge.textContent = `${safeCount} {{ __('New') }}`;
            };

            const parseRowIds = (root) => {
                return Array.from(root.querySelectorAll('tr[data-order-id]'))
                    .map((row) => Number.parseInt(row.getAttribute('data-order-id') || '0', 10))
                    .filter((id) => Number.isInteger(id) && id > 0);
            };

            const markFreshRows = (rowIds) => {
                rowIds.forEach((id) => {
                    const row = tableBody.querySelector(`tr[data-order-id="${id}"]`);
                    if (!row) {
                        return;
                    }

                    row.classList.add('order-row-fresh');
                    const newBadge = row.querySelector('[data-new-order-badge]');
                    if (newBadge) {
                        newBadge.classList.remove('d-none');
                    }
                });
            };

            const buildPollUrl = () => {
                const url = new URL(liveEndpoint, window.location.origin);
                const status = searchParams.get('status');
                const q = searchParams.get('q');
                const perPage = searchParams.get('per_page') || tableBody.dataset.currentPerPage || '10';

                if (status) {
                    url.searchParams.set('status', status);
                }
                if (q) {
                    url.searchParams.set('q', q);
                }
                if (perPage) {
                    url.searchParams.set('per_page', perPage);
                }
                url.searchParams.set('since_id', String(latestOrderId));

                return url.toString();
            };

            const poll = async () => {
                try {
                    const response = await fetch(buildPollUrl(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (!payload || typeof payload.rows_html !== 'string') {
                        return;
                    }

                    if ((payload.signature || '') === lastSignature) {
                        updateNewCountBadge(payload.new_orders_count || 0);
                        return;
                    }

                    const probe = document.createElement('tbody');
                    probe.innerHTML = payload.rows_html;
                    const incomingIds = parseRowIds(probe);
                    const freshIds = incomingIds.filter((id) => !knownOrderIds.has(id));

                    tableBody.innerHTML = payload.rows_html;
                    markFreshRows(freshIds);

                    incomingIds.forEach((id) => knownOrderIds.add(id));
                    latestOrderId = Math.max(latestOrderId, Number.parseInt(String(payload.latest_order_id || 0), 10) || 0);
                    lastSignature = payload.signature || '';
                    updateNewCountBadge(payload.new_orders_count || freshIds.length);
                } catch (_error) {
                    // Ignore transient polling errors and retry on next interval.
                }
            };

            window.setInterval(poll, pollingIntervalMs);
        })();
    </script>
@endsection

