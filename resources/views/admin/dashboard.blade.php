@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('content')
  @php $locale = app()->getLocale(); @endphp
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
      <div class="col-xxl-8 mb-6 order-0">
        <div class="card">
          <div class="d-flex align-items-start row">
            <div class="col-sm-7">
              <div class="card-body">
                <h5 class="card-title text-primary mb-3">{{ __('Congratulations!') }}</h5>
                <p class="mb-6">
                  {{ __('You have :orders orders this month.', ['orders' => number_format($stats['ordersCount'])]) }}
                </p>
                <a href="{{ route('admin.orders.index', ['locale' => $locale]) }}" class="btn btn-sm btn-outline-primary">{{ __('View Orders') }}</a>
              </div>
            </div>
            <div class="col-sm-5 text-center text-sm-left">
              <div class="card-body pb-0 px-0 px-md-6">
                <img
                  src="{{ asset('sneat-assets') }}/img/illustrations/man-with-laptop.png"
                  height="175"
                  alt="View Badge User" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">
          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="{{ asset('sneat-assets') }}/img/icons/unicons/chart-success.png"
                      alt="chart success"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt3"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">{{ __('Profit') }}</p>
                <h4 class="card-title mb-3">{{ money($stats['profit'], 2) }}</h4>
                <small class="text-success fw-medium"
                  ><i class="icon-base bx bx-up-arrow-alt"></i> +72.80%</small
                >
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 col-6 mb-6">
            <div class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img
                      src="{{ asset('sneat-assets') }}/img/icons/unicons/wallet-info.png"
                      alt="wallet info"
                      class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt6"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">{{ __('Sales') }}</p>
                <h4 class="card-title mb-3">{{ money($stats['totalSales'], 2) }}</h4>
                <small class="text-success fw-medium"
                  ><i class="icon-base bx bx-up-arrow-alt"></i> +28.42%</small
                >
              </div>
            </div>
          </div>
          <div class="col-12 mb-6">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title mb-4">{{ __('Report') }}</h5>
                <div class="d-flex flex-column gap-3">
                  <div class="d-flex justify-content-between">
                    <span>{{ __('Income') }}</span>
                    <span class="fw-medium">{{ money($stats['totalSales'], 2) }}</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>{{ __('Expense') }}</span>
                    <span class="fw-medium">{{ money($stats['totalSales'] * 0.18, 2) }}</span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>{{ __('Profit') }}</span>
                    <span class="fw-medium">{{ money($stats['profit'], 2) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
          <div class="row row-bordered g-0">
            <div class="col-lg-8">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title mb-0">
                  <h5 class="m-0 me-2">{{ __('Total Revenue') }}</h5>
                </div>
                <div class="dropdown">
                  <button
                    class="btn p-0"
                    type="button"
                    id="totalRevenue"
                    data-bs-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <i class="icon-base bx bx-dots-vertical-rounded icon-lg text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalRevenue">
                    <a class="dropdown-item" href="javascript:void(0);">{{ __('Select All') }}</a>
                    <a class="dropdown-item" href="javascript:void(0);">{{ __('Refresh') }}</a>
                    <a class="dropdown-item" href="javascript:void(0);">{{ __('Share') }}</a>
                  </div>
                </div>
              </div>
              <div
                id="totalRevenueChart"
                class="px-3"
                data-labels='@json($monthLabels)'
                data-current='@json($currentYearSeries)'
                data-previous='@json($previousYearSeries)'
                data-current-year="{{ $currentYear }}"
                data-previous-year="{{ $previousYear }}"></div>
            </div>
            <div class="col-lg-4">
              <div class="card-body px-xl-9 py-12 d-flex align-items-center flex-column">
                <div class="text-center mb-6">
                  <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary">
                      {{ $currentYear }}
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                      data-bs-toggle="dropdown"
                      aria-expanded="false">
                      <span class="visually-hidden">{{ __('Toggle Dropdown') }}</span>
                    </button>
                    <ul class="dropdown-menu">
                      <li><a class="dropdown-item" href="javascript:void(0);">{{ $currentYear }}</a></li>
                      <li><a class="dropdown-item" href="javascript:void(0);">{{ $previousYear }}</a></li>
                      <li><a class="dropdown-item" href="javascript:void(0);">{{ $previousYear - 1 }}</a></li>
                    </ul>
                  </div>
                </div>

                <div id="growthChart" data-value="{{ $growthForChart }}" data-label="{{ __('Growth') }}"></div>
                <div class="text-center fw-medium my-6 {{ $growthDirection === 'up' ? 'text-success' : 'text-danger' }}">
                  {{ $growthDisplay > 0 ? '+' : '' }}{{ $growthDisplay }}% {{ __('Company Growth') }}
                </div>

                <div class="d-flex gap-11 justify-content-between">
                  <div class="d-flex">
                    <div class="avatar me-2">
                      <span class="avatar-initial rounded-2 bg-label-primary"
                        ><i class="icon-base bx bx-dollar icon-lg text-primary"></i
                      ></span>
                    </div>
                    <div class="d-flex flex-column">
                      <small>{{ $currentYear }}</small>
                      <h6 class="mb-0">{{ money(array_sum($currentYearSeries), 1) }}</h6>
                    </div>
                  </div>
                  <div class="d-flex">
                    <div class="avatar me-2">
                      <span class="avatar-initial rounded-2 bg-label-info"
                        ><i class="icon-base bx bx-wallet icon-lg text-info"></i
                      ></span>
                    </div>
                    <div class="d-flex flex-column">
                      <small>{{ $previousYear }}</small>
                      <h6 class="mb-0">{{ money(array_sum($previousYearSeries), 1) }}</h6>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
        <div class="row">
          <div class="col-6 mb-6 payments">
            <div class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="{{ asset('sneat-assets') }}/img/icons/unicons/paypal.png" alt="paypal" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt4"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">{{ __('Payments') }}</p>
                <h4 class="card-title mb-3">{{ money($stats['totalSales'], 2) }}</h4>
                <small class="text-danger fw-medium"
                  ><i class="icon-base bx bx-down-arrow-alt"></i> -14.82%</small
                >
              </div>
            </div>
          </div>
          <div class="col-6 mb-6 transactions">
            <div class="card h-100">
              <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between mb-4">
                  <div class="avatar flex-shrink-0">
                    <img src="{{ asset('sneat-assets') }}/img/icons/unicons/cc-primary.png" alt="Credit Card" class="rounded" />
                  </div>
                  <div class="dropdown">
                    <button
                      class="btn p-0"
                      type="button"
                      id="cardOpt1"
                      data-bs-toggle="dropdown"
                      aria-haspopup="true"
                      aria-expanded="false">
                      <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('View More') }}</a>
                      <a class="dropdown-item" href="javascript:void(0);">{{ __('Delete') }}</a>
                    </div>
                  </div>
                </div>
                <p class="mb-1">{{ __('Transactions') }}</p>
                <h4 class="card-title mb-3">{{ number_format($stats['transactionsTotal']) }}</h4>
                <small class="text-success fw-medium"
                  ><i class="icon-base bx bx-up-arrow-alt"></i> +28.14%</small
                >
              </div>
            </div>
          </div>
          <div class="col-12 mb-6 profile-report">
            <div class="card h-100">
              <div class="card-body">
                <div
                  class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-10 flex-wrap">
                  <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                    <div class="card-title mb-6">
                      <h5 class="text-nowrap mb-1">{{ __('Profile Report') }}</h5>
                      <span class="badge bg-label-warning">{{ now()->year }}</span>
                    </div>
                    <div class="mt-sm-auto">
                      <span class="text-success text-nowrap fw-medium"
                        ><i class="icon-base bx bx-up-arrow-alt"></i> 68.2%</span
                      >
                      <h4 class="mb-0">{{ money($stats['totalSales'], 0) }}</h4>
                    </div>
                  </div>
                  <div id="profileReportChart" data-series='@json($profileReportSeries)'></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @canany(['reports.view', 'warehouses.view', 'inventory.view', 'orders.view', 'payments.view', 'customers.view', 'products.view'])
      <div class="row">
        <div class="col-12 mb-6">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title m-0">{{ __('Operations') }} &amp; {{ __('Reports') }}</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                @can('reports.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.operations.reports.index', ['locale' => $locale]) }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-bar-chart-alt-2"></i>
                      <span>{{ __('Reports') }}</span>
                    </a>
                  </div>
                @endcan
                @can('warehouses.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.operations.warehouses.index', ['locale' => $locale]) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-buildings"></i>
                      <span>{{ __('Warehouses') }}</span>
                    </a>
                  </div>
                @endcan
                @can('inventory.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.catalog.inventory.index', ['locale' => $locale]) }}" class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-transfer"></i>
                      <span>{{ __('Inventory') }}</span>
                    </a>
                  </div>
                @endcan
                @can('orders.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.invoices.index', ['locale' => $locale]) }}" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-receipt"></i>
                      <span>{{ __('Invoices') }}</span>
                    </a>
                  </div>
                @endcan
                @can('orders.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.orders.index', ['locale' => $locale]) }}" class="btn btn-outline-dark w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-cart"></i>
                      <span>{{ __('Orders') }}</span>
                    </a>
                  </div>
                @endcan
                @can('payments.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.sales.payments.index', ['locale' => $locale]) }}" class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-credit-card"></i>
                      <span>{{ __('Payments') }}</span>
                    </a>
                  </div>
                @endcan
                @can('customers.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-user"></i>
                      <span>{{ __('Customers') }}</span>
                    </a>
                  </div>
                @endcan
                @can('products.view')
                  <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('admin.products.index', ['locale' => $locale]) }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                      <i class="icon-base bx bx-package"></i>
                      <span>{{ __('Products') }}</span>
                    </a>
                  </div>
                @endcan
              </div>
            </div>
          </div>
        </div>
      </div>
    @endcanany

    <div class="row">
      <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between">
            <div class="card-title mb-0">
              <h5 class="mb-1 me-2">{{ __('Order Statistics') }}</h5>
              <p class="card-subtitle">{{ number_format($stats['ordersCount']) }} {{ __('Total Sales') }}</p>
            </div>
            <div class="dropdown">
              <button
                class="btn text-body-secondary p-0"
                type="button"
                id="orederStatistics"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
                <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Select All') }}</a>
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Refresh') }}</a>
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Share') }}</a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-6">
              <div class="d-flex flex-column align-items-center gap-1">
                <h3 class="mb-1">{{ number_format($stats['ordersCount']) }}</h3>
                <small>{{ __('Total Orders') }}</small>
              </div>
              <div id="orderStatisticsChart" data-series='@json($orderStatistics['series'])' data-labels='@json($orderStatistics['labels'])' data-total-label="{{ __('Total') }}"></div>
            </div>
            <ul class="p-0 m-0">
              <li class="d-flex align-items-center mb-5">
                <div class="avatar flex-shrink-0 me-3">
                  <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-time"></i></span>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">{{ __('Pending') }}</h6>
                    <small>{{ __('Awaiting approval') }}</small>
                  </div>
                  <div class="user-progress">
                    <h6 class="mb-0">{{ $orderStatistics['series'][0] }}</h6>
                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-5">
                <div class="avatar flex-shrink-0 me-3">
                  <span class="avatar-initial rounded bg-label-success"><i class="icon-base bx bx-check"></i></span>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">{{ __('Approved') }}</h6>
                    <small>{{ __('Completed') }}</small>
                  </div>
                  <div class="user-progress">
                    <h6 class="mb-0">{{ $orderStatistics['series'][1] }}</h6>
                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center mb-5">
                <div class="avatar flex-shrink-0 me-3">
                  <span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-package"></i></span>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">{{ __('Shipped') }}</h6>
                    <small>{{ __('In transit') }}</small>
                  </div>
                  <div class="user-progress">
                    <h6 class="mb-0">{{ $orderStatistics['series'][2] }}</h6>
                  </div>
                </div>
              </li>
              <li class="d-flex align-items-center">
                <div class="avatar flex-shrink-0 me-3">
                  <span class="avatar-initial rounded bg-label-danger"><i class="icon-base bx bx-x"></i></span>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0">{{ __('Cancelled') }}</h6>
                    <small>{{ __('Declined') }}</small>
                  </div>
                  <div class="user-progress">
                    <h6 class="mb-0">{{ $orderStatistics['series'][3] }}</h6>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 order-1 mb-6">
        <div class="card h-100">
          <div class="card-header nav-align-top">
            <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
              <li class="nav-item">
                <button
                  type="button"
                  class="nav-link active"
                  role="tab"
                  data-bs-toggle="tab"
                  data-bs-target="#navs-tabs-line-card-income"
                  aria-controls="navs-tabs-line-card-income"
                  aria-selected="true">
                  {{ __('Income') }}
                </button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab">{{ __('Expenses') }}</button>
              </li>
              <li class="nav-item">
                <button type="button" class="nav-link" role="tab">{{ __('Profit') }}</button>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content p-0">
              <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                <div class="d-flex mb-6">
                  <div class="avatar flex-shrink-0 me-3">
                    <img src="{{ asset('sneat-assets') }}/img/icons/unicons/wallet.png" alt="User" />
                  </div>
                  <div>
                    <p class="mb-0">{{ __('Total Balance') }}</p>
                    <div class="d-flex align-items-center">
                      <h6 class="mb-0 me-1">{{ money($stats['totalSales'], 2) }}</h6>
                      <small class="text-success fw-medium">
                        <i class="icon-base bx bx-chevron-up icon-lg"></i>
                        42.9%
                      </small>
                    </div>
                  </div>
                </div>
                <div id="incomeChart" data-series='@json($incomeSeries)' data-labels='@json($monthLabels)'></div>
                <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                  <div class="flex-shrink-0">
                    <div id="expensesOfWeek" data-value="{{ $expensePercent }}"></div>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ __('Income this week') }}</h6>
                    <small>{{ money(array_sum($weeklyRevenue), 0) }} {{ __('less than last week') }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4 order-2 mb-6">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0 me-2">{{ __('Transactions') }}</h5>
            <div class="dropdown">
              <button
                class="btn text-body-secondary p-0"
                type="button"
                id="transactionID"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
                <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Last 28 Days') }}</a>
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Last Month') }}</a>
                <a class="dropdown-item" href="javascript:void(0);">{{ __('Last Year') }}</a>
              </div>
            </div>
          </div>
          <div class="card-body pt-4">
            <ul class="p-0 m-0">
              @forelse($recentPayments as $payment)
                <li class="d-flex align-items-center mb-6">
                  <div class="avatar flex-shrink-0 me-3">
                    <img src="{{ asset('sneat-assets') }}/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                  </div>
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2">
                      <small class="d-block">{{ __(ucfirst((string) $payment->method)) }}</small>
                      <h6 class="fw-normal mb-0">
                        @if ($payment->order?->order_no)
                          {{ __('Order') }} {{ $payment->order->order_no }}
                        @else
                          {{ __('Customer Account') }} - {{ $payment->customer?->name ?? __('Customer') }}
                        @endif
                      </h6>
                    </div>
                    <div class="user-progress d-flex align-items-center gap-2">
                      <h6 class="fw-normal mb-0">{{ money($payment->amount, 2) }}</h6>
                      <span class="text-body-secondary">{{ currency_code() }}</span>
                    </div>
                  </div>
                </li>
              @empty
                <li class="text-muted">{{ __('No data.') }}</li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12 mb-6">
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title m-0">{{ __('Top Products') }}</h5>
            <a href="{{ route('admin.products.index', ['locale' => $locale]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
          </div>
          <div class="table-responsive text-nowrap">
            <table class="table">
              <thead>
                <tr>
                  <th>{{ __('Product') }}</th>
                  <th>{{ __('Category') }}</th>
                  <th>{{ __('Qty') }}</th>
                  <th>{{ __('Total Sales') }}</th>
                  <th>{{ __('Orders') }}</th>
                  <th>{{ __('Date') }}</th>
                  <th>{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($topProducts as $item)
                  @php
                    $product = $item->product;
                    $productId = (int) ($item->product_id ?? 0);
                    $productEditUrl = $productId > 0
                      ? route('admin.products.edit', ['locale' => $locale, 'product' => $productId])
                      : route('admin.products.index', ['locale' => $locale]);
                    $soldQty = (int) ($item->sold_qty ?? 0);
                    $totalSalesAmount = (float) ($item->total_sales ?? 0);
                    $ordersCountForProduct = (int) ($item->orders_count ?? 0);
                    $lastSoldAt = !empty($item->last_sold_at) ? \Illuminate\Support\Carbon::parse((string) $item->last_sold_at) : null;
                  @endphp
                  <tr data-product-link="{{ $productEditUrl }}" style="cursor: pointer;">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar me-2">
<img
    src="{{ $product?->cover_image
            ? asset('storage/' . $product->cover_image)
            : asset('sneat-assets/img/elements/2.png')
        }}"
    alt="Product"
    class="rounded"
/>                        </div>
                        <div>
                          <h6 class="mb-0">
                            <a href="{{ $productEditUrl }}" class="text-body">
                              {{ $product?->name ?? __('Product') }}
                            </a>
                          </h6>
                          <small class="text-muted">{{ number_format($soldQty) }} {{ __('sold') }}</small>
                        </div>
                      </div>
                    </td>
                    <td>{{ $product?->category?->name ?? __('Category') }}</td>
                    <td>{{ number_format($soldQty) }}</td>
                    <td>{{ money($totalSalesAmount, 2) }}</td>
                    <td>{{ number_format($ordersCountForProduct) }}</td>
                    <td>{{ $lastSoldAt?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="icon-base bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                          <a class="dropdown-item" href="{{ $productEditUrl }}">
                            <i class="icon-base bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                          </a>
                          <a class="dropdown-item" href="{{ $productEditUrl }}">
                            <i class="icon-base bx bx-show me-1"></i> {{ __('View') }}
                          </a>
                        </div>
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
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-scripts')
  <script src="{{ asset('admin-dashboard.js') }}"></script>
@endsection
