@extends('layouts.admin')

@section('title', __('Products'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Products') }}</h4>
            <p class="text-muted mb-0">{{ __('Manage your product catalog.') }}</p>
        </div>

        @can('products.create')
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>{{ __('Add product') }}
            </a>
        @endcan
    </div>

    <div class="card mb-4">
        <h5 class="card-header">{{ __('Filters') }}</h5>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.products.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            class="form-control" placeholder="{{ __('Name or SKU') }}">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Category') }}</label>
                        <select name="category_id" class="form-select">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                                    {{ app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="active" @selected(($filters['status'] ?? null) === 'active')>
                                {{ __('Active') }}
                            </option>
                            <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>
                                {{ __('Inactive') }}
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Sort by') }}</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="sort" class="form-select">
                                    <option value="created_at" @selected(($filters['sort'] ?? null) === 'created_at')>
                                        {{ __('Created') }}
                                    </option>
                                    <option value="name" @selected(($filters['sort'] ?? null) === 'name')>
                                        {{ __('Name') }}
                                    </option>
                                    <option value="price" @selected(($filters['sort'] ?? null) === 'price')>
                                        {{ __('Price') }}
                                    </option>
                                    <option value="stock" @selected(($filters['sort'] ?? null) === 'stock')>
                                        {{ __('Stock') }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="direction" class="form-select">
                                    <option value="desc" @selected(($filters['direction'] ?? null) === 'desc')>
                                        {{ __('Desc') }}
                                    </option>
                                    <option value="asc" @selected(($filters['direction'] ?? null) === 'asc')>
                                        {{ __('Asc') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter me-1"></i>{{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Products') }}</h5>
            <span class="text-muted small">{{ __('Total: :count', ['count' => $products->total()]) }}</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Image') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Stock') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                @if ($product->image)
                                    <div class="avatar avatar-sm">
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                            class="rounded">
                                    </div>
                                @else
                                    <span class="text-muted small">{{ __('N/A') }}</span>
                                @endif
                            </td>
                            <td class="text-muted text-uppercase small">{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td class="text-muted">
                                @if ($product->category)
                                    {{ app()->getLocale() === 'ar' ? $product->category->name_ar : $product->category->name_en }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ number_format($product->price, 2) }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>
                                @if ($product->status === 'active')
                                    <span class="badge bg-label-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-label-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @can('products.update')
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="btn btn-sm btn-icon btn-outline-primary">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                    @endcan

                                    @can('products.delete')
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                            onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No products found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
