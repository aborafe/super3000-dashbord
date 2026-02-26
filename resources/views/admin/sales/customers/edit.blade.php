@extends('layouts.admin')

@section('title', __('Edit Customer'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $ordersCount = (int) $customer->orders()->count(); @endphp
    @php $ledgerSummary = $ledger['summary'] ?? ['invoice_total' => 0, 'paid_total' => 0, 'balance' => 0, 'state' => 'balanced']; @endphp
    @php $ledgerOrders = $ledger['orders'] ?? []; @endphp
    @php $ledgerPayments = $ledger['payments'] ?? []; @endphp
    @php $balance = (float) ($ledgerSummary['balance'] ?? 0); @endphp
    @php $balanceState = (string) ($ledgerSummary['state'] ?? 'balanced'); @endphp
    @php $balanceTextClass = $balanceState === 'debtor' ? 'text-danger' : ($balanceState === 'creditor' ? 'text-success' : 'text-secondary'); @endphp
    @php $balanceBadgeClass = $balanceState === 'debtor' ? 'bg-label-danger' : ($balanceState === 'creditor' ? 'bg-label-success' : 'bg-label-secondary'); @endphp
    @php $balanceLabel = $balanceState === 'debtor' ? __('Debtor') : ($balanceState === 'creditor' ? __('Creditor') : __('Balanced')); @endphp
    @php $hasLedgerErrors = $errors->has('amount') || $errors->has('method') || $errors->has('paid_at') || $errors->has('notes'); @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Edit Customer') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', ['locale' => $locale]) }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}">{{ __('Customers') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Edit') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="nav-align-top">
            <ul class="nav nav-pills flex-column flex-md-row mb-4 gap-md-0 gap-2" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $hasLedgerErrors ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab">
                        <i class="icon-base bx bx-user icon-sm me-1_5"></i>{{ __('Profile') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-snapshot" type="button" role="tab">
                        <i class="icon-base bx bx-detail icon-sm me-1_5"></i>{{ __('Snapshot') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $hasLedgerErrors ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-ledger" type="button" role="tab">
                        <i class="icon-base bx bx-wallet icon-sm me-1_5"></i>{{ __('Ledger') }}
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-0">
            <div class="tab-pane fade {{ $hasLedgerErrors ? '' : 'show active' }}" id="tab-profile" role="tabpanel">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">{{ $customer->name }}</h5>
                                <div class="d-flex justify-content-between mb-2"><span>{{ __('Orders') }}</span><span class="badge bg-label-primary">{{ number_format($ordersCount) }}</span></div>
                                <div class="d-flex justify-content-between mb-2"><span>{{ __('Status') }}</span><span class="badge {{ $customer->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $customer->is_active ? __('Active') : __('Inactive') }}</span></div>
                                <div class="d-flex justify-content-between mb-2"><span>{{ __('Balance') }}</span><span class="{{ $balanceTextClass }} fw-semibold">{{ money(abs($balance), 2) }}</span></div>
                                <div class="d-flex justify-content-between"><span>{{ __('State') }}</span><span class="badge {{ $balanceBadgeClass }}">{{ $balanceLabel }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">{{ __('Customer Profile') }}</h5></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.sales.customers.update', ['locale' => $locale, 'customer' => $customer]) }}" class="row g-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control @error('name') is-invalid @enderror">
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Email') }}</label>
                                        <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control @error('email') is-invalid @enderror">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Phone') }}</label>
                                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('WhatsApp') }}</label>
                                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $customer->whatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror">
                                        @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('City') }}</label>
                                        <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="form-control @error('city') is-invalid @enderror">
                                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Password') }}</label>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Address') }}</label>
                                        <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Status') }}</label>
                                        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                            <option value="1" @selected(old('is_active', (string) (int) $customer->is_active) === '1')>{{ __('Active') }}</option>
                                            <option value="0" @selected(old('is_active', (string) (int) $customer->is_active) === '0')>{{ __('Inactive') }}</option>
                                        </select>
                                        @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                                        <button type="submit" class="btn btn-primary">{{ __('Update Customer') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-snapshot" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><span class="fw-medium">{{ __('Name') }}:</span> {{ $customer->name ?: '-' }}</li>
                            <li class="mb-2"><span class="fw-medium">{{ __('Email') }}:</span> {{ $customer->email ?: '-' }}</li>
                            <li class="mb-2"><span class="fw-medium">{{ __('Phone') }}:</span> {{ $customer->phone ?: '-' }}</li>
                            <li class="mb-2"><span class="fw-medium">{{ __('WhatsApp') }}:</span> {{ $customer->whatsapp ?: '-' }}</li>
                            <li class="mb-2"><span class="fw-medium">{{ __('City') }}:</span> {{ $customer->city ?: '-' }}</li>
                            <li><span class="fw-medium">{{ __('Address') }}:</span> {{ $customer->address ?: '-' }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $hasLedgerErrors ? 'show active' : '' }}" id="tab-ledger" role="tabpanel">
                <div class="row g-4 mb-4">
                    <div class="col-md-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ __('Balance') }}</h6><h4 class="{{ $balanceTextClass }}">{{ $balance > 0 ? '+' : ($balance < 0 ? '-' : '') }}{{ money(abs($balance), 2) }}</h4><span class="badge {{ $balanceBadgeClass }}">{{ $balanceLabel }}</span></div></div></div>
                    <div class="col-md-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ __('Invoices Total') }}</h6><h4>{{ money((float) ($ledgerSummary['invoice_total'] ?? 0), 2) }}</h4></div></div></div>
                    <div class="col-md-4"><div class="card"><div class="card-body"><h6 class="text-muted">{{ __('Payments Total') }}</h6><h4>{{ money((float) ($ledgerSummary['paid_total'] ?? 0), 2) }}</h4></div></div></div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Pay Debt / Add Credit') }}</h5>
                        <span class="badge bg-label-primary">{{ __('FIFO Allocation') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.sales.customers.payments.store', ['locale' => $locale, 'customer' => $customer]) }}" class="row g-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Method') }}</label>
                                <select name="method" class="form-select @error('method') is-invalid @enderror" required>
                                    <option value="cash" @selected(old('method', 'cash') === 'cash')>{{ __('Cash') }}</option>
                                    <option value="card" @selected(old('method') === 'card')>{{ __('Card') }}</option>
                                    <option value="transfer" @selected(old('method') === 'transfer')>{{ __('Transfer') }}</option>
                                </select>
                                @error('method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Paid At') }}</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" class="form-control @error('paid_at') is-invalid @enderror">
                                @error('paid_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">{{ __('Submit Payment') }}</button>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">{{ __('Invoices (Total / Paid / Due)') }}</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th>{{ __('Total') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Due') }}</th></tr></thead>
                                <tbody>
                                    @forelse($ledgerOrders as $invoice)
                                        <tr>
                                            <td><a href="{{ route('admin.orders.show', ['locale' => $locale, 'order' => $invoice['id']]) }}" class="text-body fw-medium">{{ $invoice['order_no'] }}</a></td>
                                            <td>{{ optional($invoice['created_at'])->format('Y-m-d') ?: '-' }}</td>
                                            <td>{{ __(ucfirst((string) $invoice['status'])) }}</td>
                                            <td>{{ money((float) $invoice['total'], 2) }}</td>
                                            <td>{{ money((float) $invoice['paid'], 2) }}</td>
                                            <td class="{{ (float) $invoice['due'] > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">{{ money((float) $invoice['due'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">{{ __('No invoices found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ __('Payments Log') }}</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th>{{ __('Source') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Allocated') }}</th><th>{{ __('Unallocated') }}</th><th>{{ __('Order') }}</th></tr></thead>
                                <tbody>
                                    @forelse($ledgerPayments as $payment)
                                        <tr>
                                            <td>{{ optional($payment['paid_at'])->format('Y-m-d') ?: '-' }}</td>
                                            <td>{{ __(ucfirst((string) $payment['method'])) }}</td>
                                            <td>{{ __(ucfirst(str_replace('_', ' ', (string) $payment['source']))) }}</td>
                                            <td>{{ money((float) $payment['amount'], 2) }}</td>
                                            <td>{{ money((float) $payment['allocated'], 2) }}</td>
                                            <td class="{{ (float) $payment['unallocated'] > 0 ? 'text-success fw-semibold' : '' }}">{{ money((float) $payment['unallocated'], 2) }}</td>
                                            <td>{{ $payment['order_no'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">{{ __('No payments found.') }}</td></tr>
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



