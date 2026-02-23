@extends('layouts.admin')

@section('title', __('Products'))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Products') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Products') }}</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('admin.catalog.products.create') }}" class="btn btn-primary">{{ __('Add Product') }}</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('admin.components.table-stats-strip')

        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ $search }}" class="form-control"
                            placeholder="{{ __('Name or SKU') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-select">
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($categoryId == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="1" @selected($status === '1')>{{ __('Active') }}</option>
                            <option value="0" @selected($status === '0')>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.catalog.products.index') }}"
                            class="btn btn-outline-secondary">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->category?->name }}</td>
                                    <td>{{ money($product->price, 2) }}</td>
                                    <td>{{ $product->stock_qty }}</td>
                                    <td>
                                        <form method="POST"
                                            action="{{ route('admin.catalog.products.toggle', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                    {{ $product->is_active ? 'checked' : '' }}
                                                    onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.catalog.products.edit', $product) }}"
                                            class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                                        <form action="{{ route('admin.catalog.products.destroy', $product) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('{{ __('Delete this product?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ __('No products found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

