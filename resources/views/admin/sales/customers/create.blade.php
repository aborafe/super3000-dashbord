@extends('layouts.admin')

@section('title', __('Create Customer'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $previewName = trim((string) old('name', '')); @endphp
    @php $previewEmail = trim((string) old('email', '')); @endphp
    @php $previewPhone = trim((string) old('phone', '')); @endphp
    @php $previewCity = trim((string) old('city', '')); @endphp
    @php $previewStatus = (string) old('is_active', '1'); @endphp
    @php $avatarSeed = $previewName !== '' ? $previewName : __('Customer'); @endphp
    @php $avatarInitial = function_exists('mb_substr') ? mb_substr($avatarSeed, 0, 1) : substr($avatarSeed, 0, 1); @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold py-3 mb-0">{{ __('Create Customer') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard', ['locale' => $locale]) }}">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}">{{ __('Customers') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Create') }}</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            <span class="avatar-initial rounded-circle bg-label-primary fs-3">{{ $avatarInitial }}</span>
                        </div>
                        <h5 class="mb-1">{{ $previewName !== '' ? $previewName : __('New Customer') }}</h5>
                        <span class="badge bg-label-primary">{{ __('Customer Account') }}</span>
                        <div class="d-flex justify-content-center gap-2 mt-4">
                            <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}"
                                class="btn btn-outline-secondary">
                                {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <h6 class="text-uppercase text-muted mb-3">{{ __('Details') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Email') }}:</span>
                                {{ $previewEmail !== '' ? $previewEmail : '-' }}
                            </li>
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Phone') }}:</span>
                                {{ $previewPhone !== '' ? $previewPhone : '-' }}
                            </li>
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('City') }}:</span>
                                {{ $previewCity !== '' ? $previewCity : '-' }}
                            </li>
                            <li class="mb-0">
                                <span class="fw-medium">{{ __('Status') }}:</span>
                                <span class="badge {{ $previewStatus === '0' ? 'bg-label-danger' : 'bg-label-success' }}">
                                    {{ $previewStatus === '0' ? __('Inactive') : __('Active') }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">{{ __('Form Checklist') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">{{ __('Name, email and phone are required.') }}</li>
                            <li class="mb-2">{{ __('Password is required for new customer.') }}</li>
                            <li class="mb-0">{{ __('Use active status to allow customer access.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">{{ __('Customer Profile') }}</h5>
                        <span class="badge bg-label-primary">{{ __('Create') }}</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.sales.customers.store', ['locale' => $locale]) }}"
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
                                <label class="form-label">{{ __('Email') }}</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Phone') }}</label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('WhatsApp') }}</label>
                                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                                    class="form-control @error('whatsapp') is-invalid @enderror">
                                @error('whatsapp')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('City') }}</label>
                                <input type="text" name="city" value="{{ old('city') }}"
                                    class="form-control @error('city') is-invalid @enderror">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('Password') }}</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('Address') }}</label>
                                <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" @selected(old('is_active', '1') === '1')>{{ __('Active') }}</option>
                                    <option value="0" @selected(old('is_active') === '0')>{{ __('Inactive') }}</option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}"
                                    class="btn btn-outline-secondary">
                                    {{ __('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>{{ __('Save Customer') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

