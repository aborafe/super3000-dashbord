@extends('layouts.admin')

@section('title', __('Order Details'))

@section('content')
    @php
        $normalizedStatus = $order->normalized_status;
        $canEditInvoiceItems = in_array($normalizedStatus, ['pending', 'approved'], true);
        $statusBadge = match ($normalizedStatus) {
            'approved', 'delivered' => 'bg-label-success',
            'shipped' => 'bg-label-info',
            'cancelled' => 'bg-label-danger',
            'returned' => 'bg-label-secondary',
            default => 'bg-label-warning',
        };

        $formItems = old('items');
        if (!is_array($formItems) || $formItems === []) {
            $formItems = $order->items
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'qty' => $item->qty,
                        'price' => number_format((float) $item->price, 2, '.', ''),
                        'base_price' => number_format((float) ($item->base_price ?? $item->price), 2, '.', ''),
                    ];
                })
                ->values()
                ->all();
        }

        $orderPaid = (float) $order->paid_amount;
        $orderItemsDiscount = (float) $order->items_discount_total;
        $hasOrderDiscount = $orderItemsDiscount > 0;
        $orderDue = max(0, (float) $order->total - $orderPaid);
    @endphp

    <style>
        .invoice-card {
            border: 1px solid rgba(67, 89, 113, .12);
            box-shadow: 0 10px 30px rgba(34, 41, 47, .05);
        }

        .invoice-card .card-header {
            border-bottom: 1px dashed rgba(67, 89, 113, .2);
        }

        .invoice-item-row {
            transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease, opacity .2s ease;
        }

        .invoice-item-row.is-dirty {
            background-color: rgba(13, 110, 253, .08);
            box-shadow: inset 4px 0 0 rgba(13, 110, 253, .6);
        }

        .invoice-item-row.row-enter {
            animation: row-in .24s ease;
        }

        .invoice-item-row.row-exit {
            opacity: 0;
            transform: translateX(16px);
        }

        @keyframes row-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .details-grid .col-span-2 {
            grid-column: 1 / -1;
        }

        @media (max-width: 575.98px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold py-3 mb-1">{{ __('Order') }} #{{ $order->order_no }}</h4>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge {{ $statusBadge }}">{{ __(ucfirst($normalizedStatus)) }}</span>
                    @if ($order->created_at)
                        <span class="text-muted small">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted d-none" data-details-save-state></span>
                <a href="{{ route('admin.invoices.print', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                    class="btn btn-outline-secondary" data-print-link>
                    <i class="icon-base bx bx-printer me-1"></i>{{ __('Print Invoice') }}
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card invoice-card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bx bx-receipt me-1 text-primary"></i>{{ __('Invoice items') }}</h5>
                        <button type="button" class="btn btn-sm btn-label-primary" data-add-item
                            @disabled(!$canEditInvoiceItems)>
                            <i class="bx bx-plus me-1"></i>{{ __('Add Item') }}
                        </button>
                    </div>
                    <div class="card-body">
                        @unless ($canEditInvoiceItems)
                            <div class="alert alert-warning py-2 px-3">
                                {{ __('Invoice items are locked after shipping or closing the order.') }}
                            </div>
                        @endunless
                        <form method="POST"
                            action="{{ route('admin.orders.items', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                            id="invoice-items-form">
                            @csrf
                            @method('PATCH')

                            <div class="table-responsive text-nowrap">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th class="w-px-30">#</th>
                                            <th>{{ __('Product') }}</th>
                                            <th class="w-px-180">{{ __('Price') }}</th>
                                            <th class="w-px-140">{{ __('Qty') }}</th>
                                            <th class="w-px-150">{{ __('Total') }}</th>
                                            <th class="w-px-160 {{ $hasOrderDiscount ? '' : 'd-none' }}"
                                                data-discount-column-header>{{ __('Discount') }}</th>
                                            <th class="w-px-80 text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody data-items-body>
                                        <tr data-empty-row class="{{ count($formItems) > 0 ? 'd-none' : '' }}">
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                {{ __('No invoice items yet. Add at least one item.') }}
                                            </td>
                                        </tr>

                                        @foreach ($formItems as $index => $itemRow)
                                            @php
                                                $selectedProductId = (int) ($itemRow['product_id'] ?? 0);
                                                $fallbackBasePrice = (float) optional(
                                                    $products->firstWhere('id', $selectedProductId),
                                                )->price;
                                                $lineTotal = (float) ($itemRow['qty'] ?? 0) * (float) ($itemRow['price'] ?? 0);
                                                $basePrice = (float) ($itemRow['base_price'] ?? ($fallbackBasePrice ?: $itemRow['price'] ?? 0));
                                                $unitDiscount = max(0, $basePrice - (float) ($itemRow['price'] ?? 0));
                                                $lineDiscount = max(0, $unitDiscount * (int) ($itemRow['qty'] ?? 0));
                                            @endphp
                                            <tr class="invoice-item-row" data-item-row
                                                data-line-total="{{ number_format($lineTotal, 2, '.', '') }}"
                                                data-base-price="{{ number_format($basePrice, 2, '.', '') }}"
                                                data-product-id="{{ $selectedProductId }}"
                                                data-discount-total="{{ number_format($lineDiscount, 2, '.', '') }}">
                                                <td><span data-row-number>{{ $loop->iteration }}</span></td>
                                                <td>
                                                    @if (!empty($itemRow['id']))
                                                        <input type="hidden" name="items[{{ $index }}][id]"
                                                            value="{{ $itemRow['id'] }}">
                                                    @endif
                                                    <select class="form-select form-select-sm" required
                                                        name="items[{{ $index }}][product_id]" data-product-select
                                                        @disabled(!$canEditInvoiceItems)>
                                                        <option value="">{{ __('Select product') }}</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                data-default-price="{{ number_format((float) $product->price, 2, '.', '') }}"
                                                                @selected((int) ($itemRow['product_id'] ?? 0) === (int) $product->id)>
                                                                {{ $product->name }}@if ($product->sku)
                                                                    ({{ $product->sku }})
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" class="form-control" min="0"
                                                            step="0.01" required
                                                            name="items[{{ $index }}][price]"
                                                            value="{{ $itemRow['price'] ?? '' }}" data-price-input
                                                            @disabled(!$canEditInvoiceItems)>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm"
                                                        min="1" step="1" required
                                                        name="items[{{ $index }}][qty]"
                                                        value="{{ $itemRow['qty'] ?? 1 }}" data-qty-input
                                                        @disabled(!$canEditInvoiceItems)>
                                                </td>
                                                <td class="fw-semibold" data-line-total-cell>
                                                    <span data-line-total>{{ money($lineTotal, 2) }}</span>
                                                </td>
                                                <td class="{{ $hasOrderDiscount ? '' : 'd-none' }}" data-line-discount-cell>
                                                    <span
                                                        class="badge bg-label-danger {{ $lineDiscount > 0 ? '' : 'd-none' }}"
                                                        data-line-discount-badge>
                                                        {{ __('Discount') }}:
                                                        <span data-line-discount>{{ money($lineDiscount, 2) }}</span>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-icon btn-label-danger"
                                                        data-remove-item title="{{ __('Remove item') }}"
                                                        @disabled(!$canEditInvoiceItems)>
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <div class="text-end">
                                    <p class="mb-1">{{ __('Subtotal') }}:
                                        <strong data-summary-subtotal>{{ money($order->subtotal, 2) }}</strong>
                                    </p>
                                    <p class="mb-1 text-danger {{ $hasOrderDiscount ? '' : 'd-none' }}"
                                        data-summary-discount-row>{{ __('Discount') }}:
                                        <strong data-summary-discount>{{ money($orderItemsDiscount, 2) }}</strong>
                                    </p>
                                    <h5 class="mb-3">{{ __('Total') }}:
                                        <strong data-summary-total>{{ money($order->total, 2) }}</strong>
                                    </h5>
                                    <button type="submit" class="btn btn-primary" @disabled(!$canEditInvoiceItems)>
                                        <i class="bx bx-save me-1"></i>{{ __('Save Invoice Items') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card invoice-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-refresh me-1 text-primary"></i>{{ __('Order status') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST"
                            action="{{ route('admin.orders.status', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                            class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="form-select form-select-sm w-auto">
                                @foreach ($availableStatusTransitions as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($normalizedStatus === $statusOption)>
                                        {{ __(ucfirst($statusOption)) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                {{ __('Update Status') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card invoice-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bx bx-git-branch me-1 text-primary"></i>{{ __('Shipping activity') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="order-timeline">
                            @foreach ($timeline as $event)
                                <li class="order-timeline-item">
                                    <span class="timeline-point timeline-point-{{ $event['type'] ?? 'primary' }}"></span>
                                    <div class="d-flex justify-content-between flex-wrap">
                                        <div>
                                            <h6 class="mb-0">{{ $event['title'] }}</h6>
                                            <small class="text-muted">{{ $event['subtitle'] }}</small>
                                        </div>
                                        <small class="text-muted">{{ $event['time']?->format('Y-m-d H:i') }}</small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card invoice-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-wallet me-1 text-primary"></i>{{ __('Partial Payment') }}</h5>
                        <span class="badge {{ $orderDue > 0 ? 'bg-label-danger' : 'bg-label-success' }}">
                            {{ $orderDue > 0 ? __('Due') : __('Paid') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Invoice Total') }}</span>
                            <span class="fw-semibold">{{ money((float) $order->total, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 {{ $hasOrderDiscount ? '' : 'd-none' }}"
                            data-payment-discount-row>
                            <span>{{ __('Discount') }}</span>
                            <span class="fw-semibold text-danger"
                                data-payment-discount-value>{{ money($orderItemsDiscount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('Paid') }}</span>
                            <span class="fw-semibold text-success">{{ money($orderPaid, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>{{ __('Due') }}</span>
                            <span class="fw-semibold {{ $orderDue > 0 ? 'text-danger' : 'text-success' }}">
                                {{ money($orderDue, 2) }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('admin.orders.payments.store', ['locale' => app()->getLocale(), 'order' => $order]) }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">{{ __('Amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}"
                                        class="form-control @error('amount') is-invalid @enderror" required>
                                </div>
                                @error('amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Method') }}</label>
                                <select name="method" class="form-select @error('method') is-invalid @enderror" required>
                                    <option value="cash" @selected(old('method', 'cash') === 'cash')>{{ __('Cash') }}</option>
                                    <option value="card" @selected(old('method') === 'card')>{{ __('Card') }}</option>
                                    <option value="transfer" @selected(old('method') === 'transfer')>{{ __('Transfer') }}</option>
                                </select>
                                @error('method')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Paid At') }}</label>
                                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}"
                                    class="form-control @error('paid_at') is-invalid @enderror">
                                @error('paid_at')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-credit-card me-1"></i>{{ __('Save Payment') }}
                                </button>
                            </div>
                        </form>

                        @php $allocationHistory = $order->paymentAllocations->sortByDesc(fn ($allocation) => $allocation->allocated_at ?? $allocation->created_at); @endphp
                        @if ($allocationHistory->count() > 0)
                            <hr>
                            <h6 class="mb-2">{{ __('Payments History') }}</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach ($allocationHistory as $allocation)
                                    @php $payment = $allocation->payment; @endphp
                                    <li class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <div class="fw-medium">
                                                {{ __(ucfirst((string) ($payment?->method ?? 'cash'))) }}
                                                -
                                                {{ money((float) $allocation->amount, 2) }}
                                            </div>
                                            <small class="text-muted">{{ optional($allocation->allocated_at ?? $allocation->created_at)->format('Y-m-d') }}</small>
                                        </div>
                                        <span class="badge bg-label-secondary">{{ __(ucfirst(str_replace('_', ' ', (string) ($payment?->source ?? 'migration')))) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <form method="POST"
                    action="{{ route('admin.orders.details', ['locale' => app()->getLocale(), 'order' => $order]) }}"
                    class="d-flex flex-column gap-4"
                    id="order-details-form">
                    @csrf
                    @method('PATCH')

                    <div class="card invoice-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-user me-1 text-primary"></i>{{ __('Customer details') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar me-3">
                                    <img src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/img/avatars/1.png"
                                        alt="Avatar" class="rounded-circle">
                                </div>
                                <div>
                                    <div class="small text-muted">{{ __('Customer ID') }} #{{ $order->customer_id }}</div>
                                    <span class="badge bg-label-primary mt-1">{{ $customerOrdersCount }} {{ __('Orders') }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Name') }}</label>
                                <input type="text" name="customer_name" class="form-control"
                                    value="{{ old('customer_name', $customerDetails['name']) }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="customer_email" class="form-control"
                                    value="{{ old('customer_email', $customerDetails['email']) }}">
                            </div>
                            <div>
                                <label class="form-label">{{ __('Mobile') }}</label>
                                <input type="text" name="customer_phone" class="form-control"
                                    value="{{ old('customer_phone', $customerDetails['phone']) }}">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">{{ __('City') }}</label>
                                <input type="text" name="customer_city" class="form-control"
                                    value="{{ old('customer_city', $customerDetails['city']) }}">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">{{ __('WhatsApp') }}</label>
                                <input type="text" name="customer_whatsapp" class="form-control"
                                    value="{{ old('customer_whatsapp', $customerDetails['whatsapp']) }}">
                            </div>
                            <div class="mt-3">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="customer_notes" class="form-control" rows="3">{{ old('customer_notes', $customerDetails['notes']) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card invoice-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-map me-1 text-primary"></i>{{ __('Shipping address') }}</h5>
                        </div>
                        <div class="card-body details-grid">
                            <div class="col-span-2">
                                <label class="form-label">{{ __('Address (from app)') }}</label>
                                <textarea name="customer_address" class="form-control" rows="2">{{ old('customer_address', $customerDetails['address']) }}</textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">{{ __('Address line 1') }}</label>
                                <input type="text" name="shipping[address_line_1]" class="form-control"
                                    value="{{ old('shipping.address_line_1', $shippingAddress['address_line_1'] ?? '') }}">
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">{{ __('Address line 2') }}</label>
                                <input type="text" name="shipping[address_line_2]" class="form-control"
                                    value="{{ old('shipping.address_line_2', $shippingAddress['address_line_2'] ?? '') }}">
                            </div>
                            <div>
                                <label class="form-label">{{ __('City') }}</label>
                                <input type="text" name="shipping[city]" class="form-control"
                                    value="{{ old('shipping.city', $shippingAddress['city'] ?? '') }}">
                            </div>
                            <div>
                                <label class="form-label">{{ __('State') }}</label>
                                <input type="text" name="shipping[state]" class="form-control"
                                    value="{{ old('shipping.state', $shippingAddress['state'] ?? '') }}">
                            </div>
                            <div>
                                <label class="form-label">{{ __('Postal code') }}</label>
                                <input type="text" name="shipping[postal_code]" class="form-control"
                                    value="{{ old('shipping.postal_code', $shippingAddress['postal_code'] ?? '') }}">
                            </div>
                            <div>
                                <label class="form-label">{{ __('Country') }}</label>
                                <input type="text" name="shipping[country]" class="form-control"
                                    value="{{ old('shipping.country', $shippingAddress['country'] ?? '') }}">
                            </div>
                            <div class="d-flex justify-content-end" style="grid-column: 1 / -1;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>{{ __('Save Customer & Shipping Details') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="invoice-item-template">
        <tr class="invoice-item-row row-enter" data-item-row data-line-total="0" data-base-price="0" data-product-id=""
            data-discount-total="0" data-new-row="1">
            <td><span data-row-number>1</span></td>
            <td>
                <select class="form-select form-select-sm" required name="items[0][product_id]" data-product-select>
                    <option value="">{{ __('Select product') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            data-default-price="{{ number_format((float) $product->price, 2, '.', '') }}">
                            {{ $product->name }}@if ($product->sku)
                                ({{ $product->sku }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" min="0" step="0.01" required
                        name="items[0][price]" value="" data-price-input>
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" min="1" step="1" required
                    name="items[0][qty]" value="1" data-qty-input>
            </td>
            <td class="fw-semibold" data-line-total-cell>
                <span data-line-total>{{ money(0, 2) }}</span>
            </td>
            <td class="d-none" data-line-discount-cell>
                <span class="badge bg-label-danger d-none" data-line-discount-badge>
                    {{ __('Discount') }}:
                    <span data-line-discount>{{ money(0, 2) }}</span>
                </span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-label-danger" data-remove-item
                    title="{{ __('Remove item') }}">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@section('page-scripts')
    <script>
        (() => {
            const itemsBody = document.querySelector('[data-items-body]');
            if (!itemsBody) {
                return;
            }

            const itemTemplate = document.getElementById('invoice-item-template');
            const addButton = document.querySelector('[data-add-item]');
            const emptyRow = itemsBody.querySelector('[data-empty-row]');
            const discountColumnHeader = document.querySelector('[data-discount-column-header]');
            const summarySubtotalNode = document.querySelector('[data-summary-subtotal]');
            const summaryDiscountRow = document.querySelector('[data-summary-discount-row]');
            const summaryDiscountNode = document.querySelector('[data-summary-discount]');
            const summaryTotalNode = document.querySelector('[data-summary-total]');
            const paymentDiscountRow = document.querySelector('[data-payment-discount-row]');
            const paymentDiscountValueNode = document.querySelector('[data-payment-discount-value]');
            const locale = document.documentElement.lang === 'ar' ? 'ar-EG' : 'en-US';
            const moneyFormatter = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: '{{ currency_code() }}',
                maximumFractionDigits: 2,
            });

            const parseNumber = (value) => {
                const parsed = Number.parseFloat(String(value ?? '').trim());
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const formatMoney = (value) => moneyFormatter.format(Number.isFinite(value) ? value : 0);

            const toggleDiscountVisibility = (hasDiscount) => {
                if (discountColumnHeader) {
                    discountColumnHeader.classList.toggle('d-none', !hasDiscount);
                }

                itemsBody.querySelectorAll('[data-line-discount-cell]').forEach((cell) => {
                    cell.classList.toggle('d-none', !hasDiscount);
                });

                if (summaryDiscountRow) {
                    summaryDiscountRow.classList.toggle('d-none', !hasDiscount);
                }

                if (paymentDiscountRow) {
                    paymentDiscountRow.classList.toggle('d-none', !hasDiscount);
                }
            };

            const markDirty = (row) => {
                row.classList.add('is-dirty');
                window.clearTimeout(row.__dirtyTimerId);
                row.__dirtyTimerId = window.setTimeout(() => {
                    row.classList.remove('is-dirty');
                }, 500);
            };

            const recalcRow = (row) => {
                const qtyInput = row.querySelector('[data-qty-input]');
                const priceInput = row.querySelector('[data-price-input]');
                const lineTotalNode = row.querySelector('[data-line-total]');
                const lineDiscountNode = row.querySelector('[data-line-discount]');
                const lineDiscountBadge = row.querySelector('[data-line-discount-badge]');

                if (!qtyInput || !priceInput || !lineTotalNode) {
                    return;
                }

                const qty = Math.max(0, Math.floor(parseNumber(qtyInput.value)));
                const price = Math.max(0, parseNumber(priceInput.value));
                const basePrice = Math.max(0, parseNumber(row.dataset.basePrice));
                const lineTotal = qty * price;
                const discountPerUnit = Math.max(0, basePrice - price);
                const discountTotal = discountPerUnit * qty;

                row.dataset.lineTotal = lineTotal.toFixed(2);
                row.dataset.discountTotal = discountTotal.toFixed(2);
                lineTotalNode.textContent = formatMoney(lineTotal);

                if (lineDiscountNode) {
                    lineDiscountNode.textContent = formatMoney(discountTotal);
                }

                if (lineDiscountBadge) {
                    lineDiscountBadge.classList.toggle('d-none', discountTotal <= 0);
                }
            };

            const recalcTotals = () => {
                const rows = itemsBody.querySelectorAll('[data-item-row]');
                let subtotal = 0;
                let totalDiscount = 0;

                rows.forEach((row) => {
                    recalcRow(row);
                    subtotal += parseNumber(row.dataset.lineTotal);
                    totalDiscount += parseNumber(row.dataset.discountTotal);
                });

                if (summarySubtotalNode) {
                    summarySubtotalNode.textContent = formatMoney(subtotal);
                }
                if (summaryDiscountNode) {
                    summaryDiscountNode.textContent = formatMoney(totalDiscount);
                }
                if (paymentDiscountValueNode) {
                    paymentDiscountValueNode.textContent = formatMoney(totalDiscount);
                }
                if (summaryTotalNode) {
                    summaryTotalNode.textContent = formatMoney(subtotal);
                }

                toggleDiscountVisibility(totalDiscount > 0.0001);
            };

            const updateEmptyState = () => {
                const hasRows = itemsBody.querySelector('[data-item-row]');
                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', Boolean(hasRows));
                }
            };

            const renumberRows = () => {
                const rows = itemsBody.querySelectorAll('[data-item-row]');
                rows.forEach((row, index) => {
                    row.querySelectorAll('input, select').forEach((field) => {
                        if (!field.name) {
                            return;
                        }
                        field.name = field.name.replace(/items\[\d+]/, `items[${index}]`);
                    });

                    const rowNumberNode = row.querySelector('[data-row-number]');
                    if (rowNumberNode) {
                        rowNumberNode.textContent = String(index + 1);
                    }
                });
            };

            const attachRowEvents = (row) => {
                const productSelect = row.querySelector('[data-product-select]');
                const qtyInput = row.querySelector('[data-qty-input]');
                const priceInput = row.querySelector('[data-price-input]');
                const removeButton = row.querySelector('[data-remove-item]');

                if (!productSelect || !qtyInput || !priceInput || !removeButton) {
                    return;
                }

                productSelect.addEventListener('change', () => {
                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                    const suggestedPrice = selectedOption ? selectedOption.dataset.defaultPrice : null;
                    const previousProductId = row.dataset.productId ?? '';
                    const selectedProductId = productSelect.value ?? '';
                    const productChanged = selectedProductId !== previousProductId;

                    if (productChanged) {
                        row.dataset.basePrice = suggestedPrice ? String(parseNumber(suggestedPrice)) : String(parseNumber(priceInput.value));
                    }

                    if (suggestedPrice && (!priceInput.value || row.dataset.newRow === '1' || productChanged)) {
                        priceInput.value = suggestedPrice;
                    }

                    row.dataset.productId = selectedProductId;
                    row.dataset.newRow = '0';
                    markDirty(row);
                    recalcTotals();
                });

                [qtyInput, priceInput].forEach((input) => {
                    input.addEventListener('input', () => {
                        markDirty(row);
                        recalcTotals();
                    });
                });

                removeButton.addEventListener('click', () => {
                    row.classList.add('row-exit');
                    window.setTimeout(() => {
                        row.remove();
                        renumberRows();
                        updateEmptyState();
                        recalcTotals();
                    }, 180);
                });
            };

            itemsBody.querySelectorAll('[data-item-row]').forEach((row) => {
                attachRowEvents(row);
            });

            if (addButton && itemTemplate) {
                addButton.addEventListener('click', () => {
                    const fragment = itemTemplate.content.cloneNode(true);
                    const newRow = fragment.querySelector('[data-item-row]');

                    if (!newRow) {
                        return;
                    }

                    itemsBody.appendChild(newRow);
                    attachRowEvents(newRow);
                    renumberRows();
                    updateEmptyState();
                    recalcTotals();

                    const productSelect = newRow.querySelector('[data-product-select]');
                    if (productSelect) {
                        productSelect.focus();
                    }
                });
            }

            renumberRows();
            updateEmptyState();
            recalcTotals();
        })();

        (() => {
            const detailsForm = document.getElementById('order-details-form');
            if (!detailsForm) {
                return;
            }

            const saveStateNode = document.querySelector('[data-details-save-state]');
            const printLink = document.querySelector('[data-print-link]');
            const detailFields = detailsForm.querySelectorAll('input, textarea, select');
            const labels = {
                saving: @json(__('Saving...')),
                saved: @json(__('Saved')),
                unsaved: @json(__('Unsaved changes')),
                failed: @json(__('Auto-save failed')),
            };

            let debounceTimerId = null;
            let inFlightSave = null;
            let hasPendingChanges = false;

            const setState = (label, tone) => {
                if (!saveStateNode) {
                    return;
                }

                saveStateNode.classList.remove('d-none', 'text-muted', 'text-success', 'text-danger', 'text-warning');
                saveStateNode.classList.add(
                    tone === 'success' ? 'text-success' :
                    tone === 'danger' ? 'text-danger' :
                    tone === 'warning' ? 'text-warning' :
                    'text-muted'
                );
                saveStateNode.textContent = label;
            };

            const persistDetails = async () => {
                if (!hasPendingChanges && !inFlightSave) {
                    return true;
                }

                if (!hasPendingChanges && inFlightSave) {
                    return inFlightSave;
                }

                setState(labels.saving, 'muted');
                const payload = new FormData(detailsForm);

                inFlightSave = fetch(detailsForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                })
                    .then(async (response) => {
                        if (!response.ok) {
                            throw new Error('auto-save-failed');
                        }

                        hasPendingChanges = false;
                        setState(labels.saved, 'success');
                        return true;
                    })
                    .catch(() => {
                        setState(labels.failed, 'danger');
                        return false;
                    })
                    .finally(() => {
                        inFlightSave = null;
                    });

                return inFlightSave;
            };

            const scheduleSave = () => {
                hasPendingChanges = true;
                setState(labels.unsaved, 'warning');
                window.clearTimeout(debounceTimerId);
                debounceTimerId = window.setTimeout(() => {
                    void persistDetails();
                }, 550);
            };

            detailFields.forEach((field) => {
                field.addEventListener('input', scheduleSave);
                field.addEventListener('change', scheduleSave);
            });

            detailsForm.addEventListener('submit', () => {
                hasPendingChanges = false;
                window.clearTimeout(debounceTimerId);
                setState(labels.saving, 'muted');
            });

            if (printLink) {
                printLink.addEventListener('click', async (event) => {
                    if (!hasPendingChanges && !inFlightSave) {
                        return;
                    }

                    event.preventDefault();
                    window.clearTimeout(debounceTimerId);
                    const isSaved = await persistDetails();

                    if (isSaved) {
                        window.location.assign(printLink.href);
                    }
                });
            }
        })();
    </script>
@endsection


