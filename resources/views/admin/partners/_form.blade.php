@csrf

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Name') }}
        </label>
        <input type="text" name="name" value="{{ old('name', $partner->name ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Phone') }}
        </label>
        <input type="text" name="phone" value="{{ old('phone', $partner->phone ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('phone')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Email') }}
        </label>
        <input type="email" name="email" value="{{ old('email', $partner->email ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('email')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Type') }}
        </label>
        <select name="role_type"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
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
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Address') }}
        </label>
        <input type="text" name="address" value="{{ old('address', $partner->address ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('address')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Notes') }}
        </label>
        <textarea name="notes" rows="3"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">{{ old('notes', $partner->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <a href="{{ route('admin.partners.index') }}"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
        {{ __('Cancel') }}
    </a>

    <button type="submit"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
