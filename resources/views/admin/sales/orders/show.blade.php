@extends('layouts.admin')

@section('title', __('Order Details'))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @php $normalizedStatus = $order->normalized_status; @endphp
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Order') }} {{ $order->order_no }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.sales.orders.index') }}">{{ __('Orders') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $order->order_no }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Order Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>{{ __('Customer') }}:</strong> {{ $order->customer?->name }}</p>
                        <p class="mb-2"><strong>{{ __('Status') }}:</strong> {{ __(ucfirst($normalizedStatus)) }}</p>
                        <p class="mb-2"><strong>{{ __('Subtotal') }}:</strong> {{ money($order->subtotal, 2) }}
                        </p>
                        <p class="mb-2"><strong>{{ __('Total') }}:</strong> {{ money($order->total, 2) }}</p>
                        <p class="mb-0"><strong>{{ __('Date') }}:</strong> {{ $order->created_at?->format('Y-m-d') }}
                        </p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Update Status') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.sales.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="pending" @selected($normalizedStatus === 'pending')>{{ __('Pending') }}</option>
                                    <option value="approved" @selected($normalizedStatus === 'approved')>{{ __('Approved') }}</option>
                                    <option value="shipped" @selected($normalizedStatus === 'shipped')>{{ __('Shipped') }}</option>
                                    <option value="delivered" @selected($normalizedStatus === 'delivered')>{{ __('Delivered') }}</option>
                                    <option value="cancelled" @selected($normalizedStatus === 'cancelled')>{{ __('Cancelled') }}</option>
                                    <option value="returned" @selected($normalizedStatus === 'returned')>{{ __('Returned') }}</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Order Items') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Qty') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Line Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->items as $item)
                                        <tr>
                                            <td>{{ $item->product?->name }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td>{{ money($item->price, 2) }}</td>
                                            <td>{{ money($item->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">{{ __('No items found.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

