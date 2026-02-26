@extends('layouts.admin')

@section('title', __('General Settings'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('General Settings') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('General Settings') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
      <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.general.update') }}" class="row g-3">
          @csrf
          @method('PUT')
          <div class="col-md-6">
            <label class="form-label">{{ __('Site Name') }}</label>
            <input type="text" name="site_name" value="{{ old('site_name', $settings['general.site_name'] ?? '') }}" class="form-control @error('site_name') is-invalid @enderror">
            @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Support Email') }}</label>
            <input type="email" name="support_email" value="{{ old('support_email', $settings['general.support_email'] ?? '') }}" class="form-control @error('support_email') is-invalid @enderror">
            @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Support Phone') }}</label>
            <input type="text" name="support_phone" value="{{ old('support_phone', $settings['general.support_phone'] ?? '') }}" class="form-control @error('support_phone') is-invalid @enderror">
            @error('support_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
