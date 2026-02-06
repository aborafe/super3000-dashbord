@extends('layouts.app')

@section('content')
    @php
        $title = __('Employees');
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">
            {{ __('Employees') }}
        </h1>

        @can('employees.create')
            <a href="{{ route('admin.employees.create') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                {{ __('Add employee') }}
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div
            class="mb-4 rounded-md border border-emerald-500/60 bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto rounded-md border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Name') }}</th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Email') }}</th>
                    <th class="px-3 py-2 text-start font-medium text-slate-600 dark:text-slate-200">{{ __('Job title') }}</th>
                    <th class="px-3 py-2 text-end font-medium text-slate-600 dark:text-slate-200">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $employee->user?->name ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $employee->user?->email ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $employee->job_title ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-end">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.employees.show', $employee) }}"
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                                    {{ __('View') }}
                                </a>
                                @can('employees.update')
                                    <a href="{{ route('admin.employees.edit', $employee) }}"
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600">
                                        {{ __('Edit') }}
                                    </a>
                                @endcan
                                @can('employees.delete')
                                    <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}"
                                        onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border border-rose-300 text-rose-700 bg-white hover:bg-rose-50 dark:bg-slate-900 dark:border-rose-500/70 dark:text-rose-200">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No employees found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            class="border-t border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
