@extends('layouts.admin')

@section('title', __('Payments'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Payments') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', ['locale' => app()->getLocale()]) }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Payments') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Order') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Source') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Allocated') }}</th>
                <th>{{ __('Unallocated') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Paid At') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($payments as $payment)
                <tr>
                  <td>
                    @if($payment->order)
                      <a href="{{ route('admin.orders.show', ['locale' => app()->getLocale(), 'order' => $payment->order]) }}" class="text-body fw-medium">{{ $payment->order->order_no }}</a>
                    @else
                      <span>-</span>
                    @endif
                  </td>
                  <td>
                    @php $paymentCustomer = $payment->customer ?? $payment->order?->customer; @endphp
                    @if($paymentCustomer)
                      <a href="{{ route('admin.sales.customers.edit', ['locale' => app()->getLocale(), 'customer' => $paymentCustomer]) }}" class="text-body">{{ $paymentCustomer->name }}</a>
                    @else
                      <span>-</span>
                    @endif
                  </td>
                  <td>{{ __(ucfirst(str_replace('_', ' ', (string) $payment->source))) }}</td>
                  <td>{{ __(ucfirst($payment->method)) }}</td>
                  <td>{{ money($payment->amount, 2) }}</td>
                  <td>{{ money((float) ($payment->allocated_amount ?? 0), 2) }}</td>
                  <td class="{{ $payment->unallocated_amount > 0 ? 'text-success fw-semibold' : '' }}">
                    {{ money($payment->unallocated_amount, 2) }}
                  </td>
                  <td>
                    @php
                      $badge = match($payment->status) {
                          'paid' => 'bg-label-success',
                          'failed' => 'bg-label-danger',
                          default => 'bg-label-warning',
                      };
                    @endphp
                    <span class="badge {{ $badge }}">{{ __(ucfirst($payment->status)) }}</span>
                  </td>
                  <td>{{ $payment->paid_at?->format('Y-m-d') ?? '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted">{{ __('No payments found.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $payments->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection


