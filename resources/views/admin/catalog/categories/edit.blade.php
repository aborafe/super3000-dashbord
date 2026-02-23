@extends('layouts.admin')

@section('title', __('Edit Category'))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Edit Category') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('admin.catalog.categories.index') }}">{{ __('Categories') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Edit') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST"
                    action="{{ route('admin.catalog.categories.update', ['locale' => app()->getLocale(), 'category' => $category->id]) }}"
                    class="row g-3" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}"
                            class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Cover Image') }}</label>
                        @if ($category->cover_image)
                            <div class="mb-1">
                                <img src="{{ asset('storage/' . $category->cover_image) }}" alt="cover"
                                    style="max-height:80px;" />
                            </div>
                        @endif
                        <input type="file" name="cover_image"
                            class="form-control @error('cover_image') is-invalid @enderror">
                        @error('cover_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                            <option value="1" @selected(old('is_active', (string) $category->is_active) === '1')>{{ __('Active') }}</option>
                            <option value="0" @selected(old('is_active', (string) $category->is_active) === '0')>{{ __('Inactive') }}</option>
                        </select>
                        @error('is_active')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        <a href="{{ route('admin.catalog.categories.index') }}"
                            class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
