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
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="mb-1">{{ __('In-store Sales') }}</p>
                                <h4 class="card-title mb-3">{{ money($stats['cashSales'], 2) }}</h4>
                                <small class="text-success fw-medium"><i class="icon-base bx bx-up-arrow-alt"></i>
                                    +5.7%</small>
                            </div>
                            <span class="badge bg-label-primary p-2">
                                <i class="icon-base bx bx-store-alt"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="mb-1">{{ __('Website Sales') }}</p>
                                <h4 class="card-title mb-3">{{ money($stats['websiteSales'], 2) }}</h4>
                                <small class="text-success fw-medium"><i class="icon-base bx bx-up-arrow-alt"></i>
                                    +12.4%</small>
                            </div>
                            <span class="badge bg-label-info p-2">
                                <i class="icon-base bx bx-globe"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="mb-1">{{ __('Discount') }}</p>
                                <h4 class="card-title mb-3">{{ money($stats['discountTotal'], 2) }}</h4>
                                <small class="text-danger fw-medium"><i class="icon-base bx bx-down-arrow-alt"></i>
                                    -3.5%</small>
                            </div>
                            <span class="badge bg-label-warning p-2">
                                <i class="icon-base bx bx-purchase-tag"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <p class="mb-1">{{ __('Affiliate') }}</p>
                                <h4 class="card-title mb-3">{{ number_format($stats['affiliateCustomers']) }}</h4>
                                <small class="text-success fw-medium"><i class="icon-base bx bx-up-arrow-alt"></i>
                                    +8.1%</small>
                            </div>
                            <span class="badge bg-label-success p-2">
                                <i class="icon-base bx bx-user-plus"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">{{ __('Filter') }}</h5>
                <form method="GET" id="products-filter-form" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected($status === 'inactive')>{{ __('Inactive') }}</option>
                        </select>
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
                        <label class="form-label">{{ __('Stock') }}</label>
                        <select name="stock" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="in" @selected($stock === 'in')>{{ __('In') }}</option>
                            <option value="low" @selected($stock === 'low')>{{ __('Low') }}</option>
                            <option value="out" @selected($stock === 'out')>{{ __('Out') }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.products.index') }}"
                            class="btn btn-outline-secondary btn-sm">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>
            <div class="card-body border-top">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <input id="products-search" type="text" name="q" value="{{ $search }}"
                            form="products-filter-form" class="form-control form-control-sm"
                            placeholder="{{ __('Search Product') }}" aria-label="{{ __('Search Product') }}" />
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select name="per_page" class="form-select form-select-sm w-auto" form="products-filter-form">
                            <option value="10" @selected($perPage === 10)>10</option>
                            <option value="25" @selected($perPage === 25)>25</option>
                            <option value="50" @selected($perPage === 50)>50</option>
                        </select>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                data-bs-toggle="dropdown">
                                <i class="icon-base bx bx-export me-1"></i>{{ __('Export') }}
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="javascript:void(0);">{{ __('CSV') }}</a>
                                <a class="dropdown-item" href="javascript:void(0);">{{ __('Excel') }}</a>
                                <a class="dropdown-item" href="javascript:void(0);">{{ __('PDF') }}</a>
                            </div>
                        </div>
                        <a href="{{ route('admin.products.create', ['locale' => app()->getLocale()]) }}"
                            class="btn btn-primary btn-sm">+
                            {{ __('Add Product') }}</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="w-px-10">
                                <input id="products-select-all" class="form-check-input" type="checkbox"
                                    name="select_all_products" />
                            </th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Category') }}</th>
                            {{-- <th>{{ __(                            php artisan migrate) }}</th> --}}
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>
                                    <input id="product-select-{{ $product->id }}" class="form-check-input"
                                        type="checkbox" name="selected_products[]" value="{{ $product->id }}" />
                                </td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center">
                                        <div class="avatar me-2">
                                            <img src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/img/elements/1.png"
                                                alt="{{ __('Product') }}" class="rounded" />
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('admin.products.edit', ['locale' => app()->getLocale(), 'product' => $product->id]) }}"
                                                class="fw-medium text-body">{{ $product->name }}</a>
                                            @if ($product->category)
                                                <a href="{{ route('admin.catalog.categories.edit', $product->category) }}"
                                                    class="small text-muted">{{ $product->category->name }}</a>
                                            @else
                                                <small class="text-muted">{{ __('Category') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar-initial rounded-circle bg-label-primary me-2">
                                            <i class="icon-base bx bx-tag"></i>
                                        </span>
                                        @if ($product->category)
                                            <a href="{{ route('admin.catalog.categories.edit', $product->category) }}"
                                                class="text-body">{{ $product->category->name }}</a>
                                        @else
                                            <span>{{ __('Category') }}</span>
                                        @endif
                                    </div>
                                </td>
                                {{-- <td>
                                    <div class="form-check form-switch">
                                        <input id="product-stock-{{ $product->id }}" class="form-check-input"
                                            type="checkbox" name="stock_visible_{{ $product->id }}"
                                            {{ $product->stock_qty > 0 ? 'checked' : '' }} disabled />
                                    </div>
                                </td> --}}
                                <td>
                                    <a href="{{ route('admin.products.edit', ['locale' => app()->getLocale(), 'product' => $product->id]) }}"
                                        class="text-body">{{ $product->sku }}</a>
                                </td>
                                <td>{{ money($product->price, 2) }}</td>
                                <td>{{ $product->stock_qty }}</td>
                                <td>
                                    <form method="POST"
                                        action="{{ route('admin.products.toggle', ['locale' => app()->getLocale(), 'product' => $product->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active"
                                                {{ $product->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <a href="{{ route('admin.products.edit', ['locale' => app()->getLocale(), 'product' => $product->id]) }}"
                                            class="btn btn-sm btn-icon btn-outline-secondary">
                                            <i class="icon-base bx bx-edit-alt"></i>
                                        </a>
                                        <div class="dropdown">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                                data-bs-toggle="dropdown">
                                                <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.products.edit', ['locale' => app()->getLocale(), 'product' => $product->id]) }}">
                                                    <i class="icon-base bx bx-edit-alt me-1"></i> {{ __('Edit') }}
                                                </a>
                                                <form
                                                    action="{{ route('admin.products.destroy', ['locale' => app()->getLocale(), 'product' => $product->id]) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="icon-base bx bx-trash me-1"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">{{ __('No products found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
                <small class="text-muted">
                    {{ __('Showing') }} {{ $products->firstItem() ?? 0 }} {{ __('to') }}
                    {{ $products->lastItem() ?? 0 }}
                    {{ __('of') }} {{ $products->total() }} {{ __('results') }}
                </small>
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection

