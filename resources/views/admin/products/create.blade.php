@extends('layouts.admin')

@section('title', __('Add product'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Add product') }}</h4>
            <p class="text-muted mb-0">{{ __('Create a new product in the catalog.') }}</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to products') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                @include('admin.products._form', [
                    'product' => $product ?? null,
                    'categories' => $categories,
                    'submitLabel' => __('Create'),
                ])
            </form>
        </div>
    </div>
@endsection
