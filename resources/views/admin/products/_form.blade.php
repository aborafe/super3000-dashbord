@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name (Arabic)') }}</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar ?? '') }}"
            class="form-control @error('name_ar') is-invalid @enderror">
        @error('name_ar')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('Name (English)') }}</label>
        <input type="text" name="name_en" value="{{ old('name_en', $product->name_en ?? '') }}"
            class="form-control @error('name_en') is-invalid @enderror">
        @error('name_en')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('SKU') }}</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
            class="form-control @error('sku') is-invalid @enderror" required>
        @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Category') }}</label>
        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">{{ __('Select category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>
                    {{ app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>
                {{ __('Active') }}
            </option>
            <option value="inactive" @selected(old('status', $product->status ?? 'active') === 'inactive')>
                {{ __('Inactive') }}
            </option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Price') }}</label>
        <input type="number" step="0.01" min="0" name="price"
            value="{{ old('price', $product->price ?? '') }}"
            class="form-control @error('price') is-invalid @enderror" required>
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Cost') }}</label>
        <input type="number" step="0.01" min="0" name="cost"
            value="{{ old('cost', $product->cost ?? '') }}"
            class="form-control @error('cost') is-invalid @enderror">
        @error('cost')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __('Stock') }}</label>
        <input type="number" step="1" min="0" name="stock"
            value="{{ old('stock', $product->stock ?? 0) }}"
            class="form-control @error('stock') is-invalid @enderror" required>
        @error('stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('Product image') }}</label>
        <input type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (! empty($product->image))
            <div class="mt-2">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded border" style="height: 72px;">
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
        {{ __('Cancel') }}
    </a>
    <button type="submit" class="btn btn-primary">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
