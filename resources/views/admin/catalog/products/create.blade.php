@extends('layouts.admin')

@section('title', __('Create Product'))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Create Product') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('admin.catalog.products.index', ['locale' => app()->getLocale()]) }}">{{ __('Products') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Create') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.catalog.products.store', ['locale' => app()->getLocale()]) }}"
                    class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('SKU') }}</label>
                        <input type="text" name="sku" value="{{ old('sku') }}"
                            class="form-control @error('sku') is-invalid @enderror">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Price') }}</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price') }}"
                            class="form-control @error('price') is-invalid @enderror">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Stock Qty') }}</label>
                        <input type="number" name="stock_qty" value="{{ old('stock_qty', 0) }}"
                            class="form-control @error('stock_qty') is-invalid @enderror">
                        @error('stock_qty')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">{{ __('Select Category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" id="catalog_is_active_create"
                                value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="catalog_is_active_create">{{ __('Active') }}</label>
                        </div>
                        @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('admin.catalog.products.index', ['locale' => app()->getLocale()]) }}"
                            class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
