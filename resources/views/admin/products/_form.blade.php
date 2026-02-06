@csrf

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Name (Arabic)') }}
        </label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('name_ar')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Name (English)') }}
        </label>
        <input type="text" name="name_en" value="{{ old('name_en', $product->name_en ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('name_en')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('SKU') }}
        </label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('sku')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Category') }}
        </label>
        <select name="category_id"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
            <option value="">{{ __('Select category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? null) == $category->id)>
                    {{ app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Price') }}
        </label>
        <input type="number" step="0.01" min="0" name="price"
            value="{{ old('price', $product->price ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('price')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Cost') }}
        </label>
        <input type="number" step="0.01" min="0" name="cost"
            value="{{ old('cost', $product->cost ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('cost')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Stock') }}
        </label>
        <input type="number" step="1" min="0" name="stock"
            value="{{ old('stock', $product->stock ?? 0) }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('stock')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Status') }}
        </label>
        <select name="status"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
            <option value="active" @selected(old('status', $product->status ?? 'active') === 'active')>
                {{ __('Active') }}
            </option>
            <option value="inactive" @selected(old('status', $product->status ?? 'active') === 'inactive')>
                {{ __('Inactive') }}
            </option>
        </select>
        @error('status')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Image URL (optional)') }}
        </label>
        <input type="text" name="image" value="{{ old('image', $product->image ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('image')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <a href="{{ route('admin.products.index') }}"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
        {{ __('Cancel') }}
    </a>

    <button type="submit"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
