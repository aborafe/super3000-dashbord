@extends('layouts.admin')

@section('title', __('Reports'))

@section('content')
  @php $locale = app()->getLocale(); @endphp
  <style>
    .report-theme {
      --report-bg: #2b3048;
      --report-bg-soft: #262b41;
      --report-border: rgba(255, 255, 255, .08);
      --report-text: #e5e9fa;
      --report-muted: #9da5cb;
      --report-accent: #6f6bff;
    }

    .report-theme .card {
      background: linear-gradient(180deg, var(--report-bg), var(--report-bg-soft));
      border: 1px solid var(--report-border);
      box-shadow: none;
      color: var(--report-text);
    }

    .report-theme .card-header {
      border-bottom: 1px solid var(--report-border);
      color: var(--report-text);
    }

    .report-theme .table {
      color: var(--report-text);
      margin-bottom: 0;
    }

    .report-theme .table>:not(caption)>*>* {
      background: transparent;
      border-bottom: 1px solid var(--report-border);
      color: inherit;
      padding: .75rem .9rem;
      vertical-align: middle;
    }

    .report-theme .table thead th {
      color: #c8ceea;
      font-size: .76rem;
      font-weight: 700;
      letter-spacing: .01em;
    }

    .report-theme .table.table-hover tbody tr:hover>* {
      background: rgba(255, 255, 255, .02);
    }

    .report-theme .text-muted {
      color: var(--report-muted) !important;
    }

    .report-count-chip {
      border: 1px solid var(--report-border);
      color: var(--report-muted);
      border-radius: .5rem;
      padding: .2rem .55rem;
      font-size: .76rem;
      background: rgba(255, 255, 255, .03);
    }

    .report-table-footer {
      border-top: 1px solid var(--report-border);
      margin-top: .8rem;
      padding-top: .8rem;
    }

    .report-theme .pagination {
      margin-bottom: 0;
      gap: .35rem;
    }

    .report-theme .page-link {
      background: transparent;
      border: 1px solid var(--report-border);
      color: #d5daf3;
      min-width: 2rem;
      text-align: center;
      border-radius: 999px;
      padding: .35rem .7rem;
    }

    .report-theme .page-item.active .page-link {
      background: var(--report-accent);
      border-color: var(--report-accent);
      color: #fff;
      box-shadow: 0 0 0 .2rem rgba(111, 107, 255, .18);
    }

    .report-theme .page-item.disabled .page-link {
      color: #7e87ad;
      background: transparent;
      border-color: var(--report-border);
    }

    .report-theme .btn-icon-soft {
      border: 1px solid var(--report-border);
      color: #d7dcf7;
      background: rgba(255, 255, 255, .03);
    }

    .report-theme .btn-icon-soft:hover {
      color: #fff;
      background: rgba(255, 255, 255, .09);
      border-color: rgba(255, 255, 255, .18);
    }
  </style>

  <div class="container-xxl flex-grow-1 container-p-y report-theme">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Reports') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', ['locale' => $locale]) }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Reports') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @include('admin.components.table-stats-strip')

    <div class="row g-4 mb-4">
      <div class="col-sm-6 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <span class="badge bg-label-primary p-2 me-3">
                <i class="bx bx-dollar-circle"></i>
              </span>
              <div>
                <h6 class="mb-0">{{ __('Total Sales') }}</h6>
                <h4 class="mb-0">{{ money($summary['totalSales'], 2) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <span class="badge bg-label-success p-2 me-3">
                <i class="bx bx-cart"></i>
              </span>
              <div>
                <h6 class="mb-0">{{ __('Orders') }}</h6>
                <h4 class="mb-0">{{ number_format($summary['ordersCount']) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <span class="badge bg-label-warning p-2 me-3">
                <i class="bx bx-line-chart"></i>
              </span>
              <div>
                <h6 class="mb-0">{{ __('Avg Order') }}</h6>
                <h4 class="mb-0">{{ money($summary['avgOrder'] ?? 0, 2) }}</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('Sales by Day') }}</h5>
            <span class="report-count-chip">{{ __('Show :count results', ['count' => $perPage]) }}</span>
          </div>
          <div class="card-body">
            <div class="table-responsive text-nowrap">
              <table class="table table-hover reports-table">
                <thead>
                  <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Total Sales') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($salesByDay as $row)
                    <tr>
                      <td>{{ $row->date }}</td>
                      <td>{{ money($row->total, 2) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="2" class="text-center text-muted">{{ __('No data.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 report-table-footer">
              <small class="text-muted">
                {{ __('Showing :from to :to of :total results', [
                  'from' => $salesByDay->firstItem() ?? 0,
                  'to' => $salesByDay->lastItem() ?? 0,
                  'total' => $salesByDay->total(),
                ]) }}
              </small>
              {{ $salesByDay->onEachSide(1)->links() }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('Top Products') }}</h5>
            <span class="report-count-chip">{{ __('Show :count results', ['count' => $perPage]) }}</span>
          </div>
          <div class="card-body">
            <div class="table-responsive text-nowrap">
              <table class="table table-hover reports-table">
                <thead>
                  <tr>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Qty') }}</th>
                    <th>{{ __('Sales') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($topProducts as $row)
                    <tr>
                      <td>{{ $row->product?->name }}</td>
                      <td>{{ $row->total_qty }}</td>
                      <td>{{ money($row->total_sales, 2) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="3" class="text-center text-muted">{{ __('No data.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 report-table-footer">
              <small class="text-muted">
                {{ __('Showing :from to :to of :total results', [
                  'from' => $topProducts->firstItem() ?? 0,
                  'to' => $topProducts->lastItem() ?? 0,
                  'total' => $topProducts->total(),
                ]) }}
              </small>
              {{ $topProducts->onEachSide(1)->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mt-1">
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted mb-2">{{ __('Total Debts') }}</h6>
            <h4 class="mb-0 text-danger">{{ money((float) ($ledgerSummary['total_debts'] ?? 0), 2) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted mb-2">{{ __('Total Credits') }}</h6>
            <h4 class="mb-0 text-success">{{ money((float) ($ledgerSummary['total_credits'] ?? 0), 2) }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h6 class="text-muted mb-2">{{ __('Net Position') }}</h6>
            <h4 class="mb-0 {{ ((float) ($ledgerSummary['net_position'] ?? 0)) < 0 ? 'text-danger' : 'text-success' }}">
              {{ money((float) ($ledgerSummary['net_position'] ?? 0), 2) }}
            </h4>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ __('Customer Ledger') }}</h5>
        <span class="report-count-chip">{{ __('Show :count results', ['count' => $perPage]) }}</span>
      </div>
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover reports-table">
            <thead>
              <tr>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Invoices') }}</th>
                <th>{{ __('Payments') }}</th>
                <th>{{ __('Balance') }}</th>
                <th>{{ __('State') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($customerLedger as $row)
                <tr>
                  <td>
                    <a href="{{ route('admin.sales.customers.edit', ['locale' => app()->getLocale(), 'customer' => $row['id']]) }}" class="text-body">
                      {{ $row['name'] }}
                    </a>
                  </td>
                  <td>{{ money((float) $row['invoice_total'], 2) }}</td>
                  <td>{{ money((float) $row['paid_total'], 2) }}</td>
                  <td class="{{ (float) $row['balance'] < 0 ? 'text-danger' : ((float) $row['balance'] > 0 ? 'text-success' : 'text-muted') }}">
                    {{ (float) $row['balance'] > 0 ? '+' : ((float) $row['balance'] < 0 ? '-' : '') }}{{ money(abs((float) $row['balance']), 2) }}
                  </td>
                  <td>
                    <span class="badge {{
                      $row['state'] === 'debtor' ? 'bg-label-danger' :
                      ($row['state'] === 'creditor' ? 'bg-label-success' : 'bg-label-secondary')
                    }}">
                      {{ $row['state'] === 'debtor' ? __('Debtor') : ($row['state'] === 'creditor' ? __('Creditor') : __('Balanced')) }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">{{ __('No data.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 report-table-footer">
          <small class="text-muted">
            {{ __('Showing :from to :to of :total results', [
              'from' => $customerLedger->firstItem() ?? 0,
              'to' => $customerLedger->lastItem() ?? 0,
              'total' => $customerLedger->total(),
            ]) }}
          </small>
          {{ $customerLedger->onEachSide(1)->links() }}
        </div>
      </div>
    </div>

    <div class="row g-4 mt-1">
      <div class="col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('Open Invoices') }}</h5>
            <span class="report-count-chip">{{ __('Show :count results', ['count' => $perPage]) }}</span>
          </div>
          <div class="card-body">
            <div class="table-responsive text-nowrap">
              <table class="table table-hover reports-table">
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
                  @forelse($openInvoices as $invoice)
                    @php
                      $statusClass = match ($invoice['status']) {
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
                        <a href="{{ route('admin.orders.show', ['locale' => $locale, 'order' => $invoice['id']]) }}" class="text-body fw-semibold">
                          {{ $invoice['order_no'] }}
                        </a>
                      </td>
                      <td>{{ $invoice['customer_name'] }}</td>
                      <td><span class="badge {{ $statusClass }}">{{ __(ucfirst((string) $invoice['status'])) }}</span></td>
                      <td>{{ money((float) $invoice['paid'], 2) }}</td>
                      <td class="text-danger fw-semibold">{{ money((float) $invoice['due'], 2) }}</td>
                      <td>{{ money((float) $invoice['total'], 2) }}</td>
                      <td>{{ optional($invoice['created_at'])->format('Y-m-d') ?: '-' }}</td>
                      <td>
                        <a href="{{ route('admin.orders.show', ['locale' => $locale, 'order' => $invoice['id']]) }}" class="btn btn-sm btn-icon btn-icon-soft" title="{{ __('View') }}">
                          <i class="bx bx-show"></i>
                        </a>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center text-muted">{{ __('No data.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 report-table-footer">
              <small class="text-muted">
                {{ __('Showing :from to :to of :total results', [
                  'from' => $openInvoices->firstItem() ?? 0,
                  'to' => $openInvoices->lastItem() ?? 0,
                  'total' => $openInvoices->total(),
                ]) }}
              </small>
              {{ $openInvoices->onEachSide(1)->links() }}
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('Payments Log') }}</h5>
            <span class="report-count-chip">{{ __('Show :count results', ['count' => $perPage]) }}</span>
          </div>
          <div class="card-body">
            <div class="table-responsive text-nowrap">
              <table class="table table-hover reports-table">
                <thead>
                  <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Allocated') }}</th>
                    <th>{{ __('Unallocated') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($paymentsLog as $payment)
                    <tr>
                      <td>{{ optional($payment['paid_at'])->format('Y-m-d') ?: '-' }}</td>
                      <td>{{ $payment['customer_name'] }}</td>
                      <td>{{ money((float) $payment['amount'], 2) }}</td>
                      <td>{{ money((float) $payment['allocated'], 2) }}</td>
                      <td class="{{ (float) $payment['unallocated'] > 0 ? 'text-success fw-semibold' : '' }}">
                        {{ money((float) $payment['unallocated'], 2) }}
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted">{{ __('No data.') }}</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 report-table-footer">
              <small class="text-muted">
                {{ __('Showing :from to :to of :total results', [
                  'from' => $paymentsLog->firstItem() ?? 0,
                  'to' => $paymentsLog->lastItem() ?? 0,
                  'total' => $paymentsLog->total(),
                ]) }}
              </small>
              {{ $paymentsLog->onEachSide(1)->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

