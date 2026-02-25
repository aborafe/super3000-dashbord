@extends('layouts.admin')

@section('title', __('Categories'))

@section('content')
    @php
        /** @var \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\LengthAwarePaginator<\App\Models\Category>|array<int, \App\Models\Category> $categories */
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Categories') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Categories') }}</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('admin.catalog.categories.create') }}" class="btn btn-primary">{{ __('Add Category') }}</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @include('admin.components.table-stats-strip')

        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ $search }}" class="form-control"
                            placeholder="{{ __('Category name') }}">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                        <a href="{{ route('admin.catalog.categories.index') }}"
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
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}"
                                            class="text-body fw-medium">{{ $category->name }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}"
                                            class="text-body">{{ $category->slug }}</a>
                                    </td>
                                    <td>
                                        <form method="POST"
                                            action="{{ route('admin.catalog.categories.toggle', $category) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                    {{ $category->is_active ? 'checked' : '' }}
                                                    onchange="this.form.submit()">
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.catalog.categories.edit', $category) }}"
                                            class="btn btn-sm btn-outline-secondary">{{ __('Edit') }}</a>
                                        <form action="{{ route('admin.catalog.categories.destroy', $category) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('{{ __('Delete this category?') }}')">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">{{ __('No categories found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($categories instanceof \Illuminate\Contracts\Pagination\Paginator)
                    <div class="mt-3">
                        {{ $categories->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
