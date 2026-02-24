<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Products') }}</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d9d9d9; padding: 8px; font-size: 13px; }
        th { background: #f3f5ff; font-weight: 700; text-align: start; }
        .meta { margin-bottom: 12px; color: #333; font-size: 13px; }
    </style>
</head>
<body>
    <div class="meta">
        <strong>{{ __('Products') }}</strong> -
        {{ __('Date') }}: {{ $exportedAt->format('Y-m-d H:i') }}
    </div>

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
                    <td>{{ number_format((float) $product->price, 2) }}</td>
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

