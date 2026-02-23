@extends('layouts.admin')

@section('title', __('Create Warehouse'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Create Warehouse') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.operations.warehouses.index') }}">{{ __('Warehouses') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.operations.warehouses.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label">{{ __('Name') }}</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Location') }}</label>
            <input type="text" name="location" value="{{ old('location') }}" class="form-control @error('location') is-invalid @enderror">
            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
              <option value="1" @selected(old('is_active', '1') === '1')>{{ __('Active') }}</option>
              <option value="0" @selected(old('is_active') === '0')>{{ __('Inactive') }}</option>
            </select>
            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('admin.operations.warehouses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
