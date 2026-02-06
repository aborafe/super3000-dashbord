@csrf

@php
    $user = $employee->user ?? null;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Name') }}
        </label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Email') }}
        </label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            required>
        @error('email')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Password') }}
        </label>
        <input type="password" name="password"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800"
            @if (! isset($employee)) required @endif>
        @error('password')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @if (isset($employee))
            <p class="mt-1 text-xs text-slate-400">{{ __('Leave blank to keep current password.') }}</p>
        @endif
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Job title') }}
        </label>
        <input type="text" name="job_title" value="{{ old('job_title', $employee->job_title ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('job_title')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">
            {{ __('Phone') }}
        </label>
        <input type="text" name="phone" value="{{ old('phone', $employee->phone ?? '') }}"
            class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
        @error('phone')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-2">
    <a href="{{ route('admin.employees.index') }}"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
        {{ __('Cancel') }}
    </a>

    <button type="submit"
        class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-sky-500 bg-sky-600 text-white hover:bg-sky-700">
        {{ $submitLabel ?? __('Save') }}
    </button>
</div>
