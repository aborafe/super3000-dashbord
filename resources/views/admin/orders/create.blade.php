@extends('layouts.admin')

@section('title', __('Create order'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Create order') }}</h4>
            <p class="text-muted mb-0">{{ __('Create a new customer order.') }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to orders') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.orders.store') }}">
                @include('admin.orders._form', [
                    'partners' => $partners,
                    'products' => $products,
                    'items' => old('items', []),
                    'submitLabel' => __('Create'),
                ])
            </form>
        </div>
    </div>
@endsection

@push('vendor-scripts')
    <script defer src="{{ asset('admin/assets/vendor/libs/alpinejs/alpine.min.js') }}"></script>
@endpush
