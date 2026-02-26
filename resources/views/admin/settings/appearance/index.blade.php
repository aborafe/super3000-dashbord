@extends('layouts.admin')

@section('title', __('Appearance'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Appearance') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Appearance') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.appearance.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-4">
            <label class="form-label">{{ __('Theme') }}</label>
            <select name="theme" class="form-select @error('theme') is-invalid @enderror">
              <option value="light" @selected(old('theme', $settings['appearance.theme'] ?? 'light') === 'light')>{{ __('Light') }}</option>
              <option value="dark" @selected(old('theme', $settings['appearance.theme'] ?? 'light') === 'dark')>{{ __('Dark') }}</option>
            </select>
            @error('theme')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('RTL') }}</label>
            <select name="rtl" class="form-select @error('rtl') is-invalid @enderror">
              <option value="0" @selected(old('rtl', $settings['appearance.rtl'] ?? '0') === '0')>{{ __('Disabled') }}</option>
              <option value="1" @selected(old('rtl', $settings['appearance.rtl'] ?? '0') === '1')>{{ __('Enabled') }}</option>
            </select>
            @error('rtl')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
