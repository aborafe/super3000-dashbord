@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" value="{{ old('name', $partner->name ?? '') }}"
            class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Phone') }}</label>
        <input type="text" name="phone" value="{{ old('phone', $partner->phone ?? '') }}"
            class="form-control @error('phone') is-invalid @enderror" required>
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Email') }}</label>
        <input type="email" name="email" value="{{ old('email', $partner->email ?? '') }}"
            class="form-control @error('email') is-invalid @enderror">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Type') }}</label>
        <select name="role_type" class="form-select @error('role_type') is-invalid @enderror" required>
            <option value="wholesale" @selected(old('role_type', $partner->role_type ?? 'both') === 'wholesale')>
                {{ __('Wholesale') }}
            </option>
            <option value="retail" @selected(old('role_type', $partner->role_type ?? 'both') === 'retail')>
                {{ __('Retail') }}
            </option>
            <option value="both" @selected(old('role_type', $partner->role_type ?? 'both') === 'both')>
                {{ __('Both') }}
            </option>
        </select>
        @error('role_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Address') }}</label>
        <input type="text" name="address" value="{{ old('address', $partner->address ?? '') }}"
            class="form-control @error('address') is-invalid @enderror">
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $partner->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
