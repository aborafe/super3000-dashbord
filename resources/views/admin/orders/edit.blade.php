@extends('layouts.app')

@section('content')
    @php
        $title = __('Edit order');
        $items = old('items', $order->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'price' => $item->price,
            ];
        })->toArray());
    @endphp

    <div class="mb-4">
        <h1 class="text-xl font-semibold">{{ __('Edit order') }}</h1>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.orders.update', $order) }}">
            @method('PUT')
            @include('admin.orders._form', [
                'order' => $order,
                'partners' => $partners,
                'products' => $products,
                'items' => $items,
                'submitLabel' => __('Update'),
            ])
        </form>
    </div>
@endsection
