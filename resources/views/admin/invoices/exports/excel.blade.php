<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice List') }}</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d9d9d9; padding: 8px; font-size: 13px; }
        th { background: #f3f5ff; font-weight: 700; text-align: start; }
        .meta { margin-bottom: 12px; color: #333; font-size: 13px; }
    </style>
</head>
<body>
    <div class="meta">
        <strong>{{ __('Invoice List') }}</strong> -
        {{ __('Date') }}: {{ $exportedAt->format('Y-m-d H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Invoice') }}</th>
                <th>{{ __('Customer') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Discount') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $order)
                <tr>
                    <td>{{ $order->order_no }}</td>
                    <td>{{ $order->customer?->name ?? '-' }}</td>
                    <td>{{ number_format((float) $order->total, 2) }}</td>
                    <td>{{ number_format((float) $order->items_discount_total, 2) }}</td>
                    <td>{{ __(ucfirst($order->normalized_status)) }}</td>
                    <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('No data.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

