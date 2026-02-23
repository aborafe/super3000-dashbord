@extends('layouts.admin')

@section('title', __('Create Movement'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Create Inventory Movement') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.catalog.inventory.index') }}">{{ __('Inventory') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Create') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.catalog.inventory.store') }}" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label">{{ __('Product') }}</label>
            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
              <option value="">{{ __('Select Product') }}</option>
              @foreach($products as $product)
                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
              @endforeach
            </select>
            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Warehouse') }}</label>
            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
              <option value="">{{ __('Select Warehouse') }}</option>
              @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
              @endforeach
            </select>
            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Type') }}</label>
            <select name="type" class="form-select @error('type') is-invalid @enderror">
              <option value="in" @selected(old('type', 'in') === 'in')>{{ __('In') }}</option>
              <option value="out" @selected(old('type') === 'out')>{{ __('Out') }}</option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('Qty') }}</label>
            <input type="number" name="qty" value="{{ old('qty', 1) }}" class="form-control @error('qty') is-invalid @enderror">
            @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-12">
            <label class="form-label">{{ __('Note') }}</label>
            <textarea name="note" class="form-control @error('note') is-invalid @enderror" rows="2">{{ old('note') }}</textarea>
            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('admin.catalog.inventory.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
