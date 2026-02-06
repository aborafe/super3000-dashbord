@extends('layouts.app')

@section('content')
    @php
        $title = __('Employee details');
    @endphp

    <div class="mb-4 flex items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">
                {{ $employee->user?->name ?? __('Employee') }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $employee->user?->email ?? '—' }}
            </p>
        </div>

        <a href="{{ route('admin.employees.index') }}"
            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
            {{ __('Back to list') }}
        </a>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
        <dl class="space-y-2">
            <div class="flex items-center justify-between">
                <dt class="text-slate-500 dark:text-slate-400">{{ __('Job title') }}</dt>
                <dd class="font-medium">{{ $employee->job_title ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-slate-500 dark:text-slate-400">{{ __('Phone') }}</dt>
                <dd class="font-medium">{{ $employee->phone ?: '—' }}</dd>
            </div>
        </dl>
    </div>
@endsection
