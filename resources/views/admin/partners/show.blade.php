@extends('layouts.app')

@section('content')
    @php
        $title = __('Partner details');
    @endphp

    <div class="mb-4 flex items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">
                {{ $partner->name }}
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $partner->phone }}
            </p>
        </div>

        <a href="{{ route('admin.partners.index') }}"
            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-600">
            {{ __('Back to list') }}
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Contact') }}
            </h2>
            <dl class="space-y-1">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Email') }}</dt>
                    <dd class="font-medium">{{ $partner->email ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Address') }}</dt>
                    <dd class="font-medium">{{ $partner->address ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Type') }}</dt>
                    <dd class="font-medium">{{ ucfirst($partner->role_type) }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Stats') }}
            </h2>
            <dl class="space-y-1">
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Orders count') }}</dt>
                    <dd class="font-medium">{{ $partner->orders->count() }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-slate-500 dark:text-slate-400">{{ __('Debts count') }}</dt>
                    <dd class="font-medium">{{ $partner->debts->count() }}</dd>
                </div>
            </dl>
        </section>
    </div>

    @if ($partner->notes)
        <section class="mt-4 rounded-md border border-slate-200 bg-white p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('Notes') }}
            </h2>
            <p class="text-slate-600 dark:text-slate-300">
                {{ $partner->notes }}
            </p>
        </section>
    @endif
@endsection
