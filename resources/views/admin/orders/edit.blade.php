@extends('layouts.admin')

@section('title', __('Edit order'))

@section('content')
    @php
        $items = old('items', $order->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'price' => $item->price,
            ];
        })->toArray());
    @endphp

    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Edit order') }}</h4>
            <p class="text-muted mb-0">{{ __('Update order details and items.') }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to orders') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
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
    </div>
@endsection

@push('vendor-scripts')
    <script defer src="{{ asset('admin/assets/vendor/libs/alpinejs/alpine.min.js') }}"></script>
@endpush
