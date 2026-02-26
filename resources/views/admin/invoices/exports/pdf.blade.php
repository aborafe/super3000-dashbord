<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Invoice List') }} - PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 24px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 14px; color: #555; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d9d9; padding: 7px; font-size: 12px; text-align: start; }
        th { background: #f3f5ff; }
    </style>
</head>
<body>
    <h2>{{ __('Invoice List') }}</h2>
    <div class="meta">{{ __('Date') }}: {{ $exportedAt->format('Y-m-d H:i') }}</div>

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
                    <td>{{ money($order->total, 2) }}</td>
                    <td>{{ money($order->items_discount_total, 2) }}</td>
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
