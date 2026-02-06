@extends('layouts.admin')

@section('title', __('Edit product'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Edit product') }}</h4>
            <p class="text-muted mb-0">{{ __('Update the product details.') }}</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to products') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('admin.products._form', [
                    'product' => $product,
                    'categories' => $categories,
                    'submitLabel' => __('Update'),
                ])
            </form>
        </div>
    </div>
@endsection
