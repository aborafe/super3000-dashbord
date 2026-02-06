@csrf

@php
    $user = $employee->user ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
            class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Email') }}</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
            class="form-control @error('email') is-invalid @enderror" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Password') }}</label>
        <input type="password" name="password"
            class="form-control @error('password') is-invalid @enderror"
            @if (! isset($employee)) required @endif>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (isset($employee))
            <small class="text-muted">{{ __('Leave blank to keep current password.') }}</small>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Job title') }}</label>
        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title ?? '') }}"
            class="form-control @error('job_title') is-invalid @enderror">
        @error('job_title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $employee->phone ?? '') }}"
            class="form-control @error('phone') is-invalid @enderror">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
