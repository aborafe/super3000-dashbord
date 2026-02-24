<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Products') }} - PDF</title>
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
    <h2>{{ __('Products') }}</h2>
    <div class="meta">{{ __('Date') }}: {{ $exportedAt->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('SKU') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Qty') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? '-' }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ money($product->price, 2) }}</td>
                    <td>{{ (int) $product->stock_qty }}</td>
                    <td>{{ $product->is_active ? __('Active') : __('Inactive') }}</td>
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
