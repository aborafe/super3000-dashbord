@extends('layouts.blank')

@section('title', __('Invoice'))

@section('content')
    @php
        /** @var \App\Models\Order $order */
        $customerName = $order->customer?->name ?? '';
        $customerEmail = $order->customer?->email ?? '';
        $customerPhone = $order->customer?->phone ?? '';
        $itemsDiscountAmount = round((float) $order->items_discount_total, 2);
        $hasOrderDiscount = $itemsDiscountAmount > 0;
        $paidAmount = round((float) $order->paid_amount, 2);
        $remainingAmount = round(max(0, (float) $order->due_amount), 2);
        $customerCity = trim((string) ($order->customer?->city ?? ''));
        $customerWhatsapp = trim((string) ($order->customer?->whatsapp ?? ''));
        $customerAddress = trim((string) ($order->customer?->address ?? ''));
        $customerNotes = trim((string) ($order->customer_notes ?? ''));
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $shippingCity = trim((string) ($shippingAddress['city'] ?? $customerCity));
        $shippingLines = array_filter([
            $shippingAddress['address_line_1'] ?? null,
            $shippingAddress['address_line_2'] ?? null,
            trim(implode(', ', array_filter([$shippingCity, $shippingAddress['state'] ?? null]))),
            trim(implode(' ', array_filter([$shippingAddress['postal_code'] ?? null, $shippingAddress['country'] ?? null]))),
        ]);
        $resolvedAddressLines = [];
        if ($customerAddress !== '') {
            $resolvedAddressLines[] = $customerAddress;
        }
        foreach ($shippingLines as $line) {
            if (!in_array($line, $resolvedAddressLines, true)) {
                $resolvedAddressLines[] = $line;
            }
        }
    @endphp

    <style>
        .invoice-shell {
            max-width: 1020px;
            margin: 0 auto;
        }

        .invoice-head {
            border: 1px solid rgba(67, 89, 113, .15);
            border-radius: .75rem;
            padding: 1.2rem;
            margin-bottom: 1rem;
            background: linear-gradient(145deg, rgba(13, 110, 253, .06), rgba(255, 255, 255, .5));
        }

        .invoice-block {
            border: 1px solid rgba(67, 89, 113, .15);
            border-radius: .75rem;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .invoice-block .block-title {
            font-weight: 700;
            margin-bottom: .65rem;
        }

        .print-watermark {
            display: none;
        }

        @media print {
            .print-area,
            .print-area * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-area {
                position: relative;
                isolation: isolate;
            }

            .print-watermark {
                display: block;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 620px;
                opacity: .16;
                pointer-events: none;
                z-index: 0;
            }

            .print-area .invoice-shell {
                position: relative;
                z-index: 1;
            }

            .invoice-head,
            .invoice-block.card {
                background: rgba(255, 255, 255, .68) !important;
            }

            .invoice-block .card-body,
            .invoice-block .table,
            .invoice-block .table th,
            .invoice-block .table td {
                background: transparent !important;
            }

            .invoice-shell {
                max-width: none;
            }
        }
    </style>

    <div class="container-xxl flex-grow-1 container-p-y print-area">
        <img src="{{ asset('logo.png') }}" alt="Watermark" class="print-watermark">
        <div class="invoice-shell">
            <div class="invoice-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h4 class="fw-bold mb-1">{{ __('Invoice') }} #{{ $order->order_no }}</h4>
                    <p class="mb-1 text-muted">{{ __('Date') }}: {{ $order->created_at?->format('Y-m-d') }}</p>
                    <p class="mb-0 text-muted">{{ __('Status') }}: {{ __(ucfirst($order->status)) }}</p>
                </div>
                <div class="text-end">
                    <h6 class="mb-1">{{ __('Customer') }}</h6>
                    <p class="mb-0 fw-semibold">{{ $customerName }}</p>
                    @if ($customerEmail)
                        <p class="mb-0">{{ $customerEmail }}</p>
                    @endif
                    @if ($customerPhone)
                        <p class="mb-0">{{ $customerPhone }}</p>
                    @endif
                    @if ($customerCity)
                        <p class="mb-0">{{ __('City') }}: {{ $customerCity }}</p>
                    @endif
                    @if ($customerWhatsapp)
                        <p class="mb-0">{{ __('WhatsApp') }}: {{ $customerWhatsapp }}</p>
                    @endif
                </div>
            </div>

            <div class="invoice-block card">
                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Qty') }}</th>
                                    <th>{{ __('Total') }}</th>
                                    @if ($hasOrderDiscount)
                                        <th>{{ __('Discount') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($order->items as $item)
                                    @php
                                        /** @var \App\Models\OrderItem $item */
                                        $lineDiscount = (float) $item->discount_total;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->product?->name ?? __('Unknown product') }}</td>
                                        <td>{{ money((float) $item->price, 2) }}</td>
                                        <td>{{ $item->qty }}</td>
                                        <td>{{ money((float) $item->line_total, 2) }}</td>
                                        @if ($hasOrderDiscount)
                                            <td class="{{ $lineDiscount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                                {{ $lineDiscount > 0 ? money($lineDiscount, 2) : '' }}
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $hasOrderDiscount ? 5 : 4 }}"
                                            class="text-center text-muted">{{ __('No items found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <div class="text-end">
                            <p class="mb-1">{{ __('Subtotal') }}: <strong>{{ money((float) $order->subtotal, 2) }}</strong>
                            </p>
                            @if ($hasOrderDiscount)
                                <p class="mb-1 text-danger">{{ __('Discount') }}:
                                    <strong>{{ money($itemsDiscountAmount, 2) }}</strong>
                                </p>
                            @endif
                            <h5 class="mb-1">{{ __('Total') }}: <strong>{{ money((float) $order->total, 2) }}</strong>
                            </h5>
                            <p class="mb-1 text-success">{{ __('Paid') }}: <strong>{{ money($paidAmount, 2) }}</strong></p>
                            <p class="mb-0 {{ $remainingAmount > 0 ? 'text-danger' : 'text-success' }}">
                                {{ __('Remaining') }}: <strong>{{ money($remainingAmount, 2) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="invoice-block card h-100">
                        <div class="card-body">
                            <h6 class="block-title">{{ __('Shipping address') }}</h6>
                            @forelse ($resolvedAddressLines as $line)
                                <p class="mb-1">{{ $line }}</p>
                            @empty
                                <p class="mb-0 text-muted">{{ __('No shipping address provided.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="invoice-block card h-100">
                        <div class="card-body">
                            <h6 class="block-title">{{ __('Customer details') }}</h6>
                            <p class="mb-1">{{ $customerName ?: __('Not provided') }}</p>
                            @if ($customerEmail)
                                <p class="mb-1">{{ $customerEmail }}</p>
                            @endif
                            @if ($customerPhone)
                                <p class="mb-1">{{ $customerPhone }}</p>
                            @endif
                            @if ($customerCity)
                                <p class="mb-1">{{ __('City') }}: {{ $customerCity }}</p>
                            @endif
                            @if ($customerWhatsapp)
                                <p class="mb-1">{{ __('WhatsApp') }}: {{ $customerWhatsapp }}</p>
                            @endif
                            @if ($customerNotes)
                                <hr class="my-2">
                                <p class="mb-0"><strong>{{ __('Notes') }}:</strong> {{ $customerNotes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

