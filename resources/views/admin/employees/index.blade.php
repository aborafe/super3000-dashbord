@extends('layouts.admin')

@section('title', __('Employees'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Employees') }}</h4>
            <p class="text-muted mb-0">{{ __('Manage your team members.') }}</p>
        </div>

        @can('employees.create')
            <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>{{ __('Add employee') }}
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Employees') }}</h5>
            <span class="text-muted small">{{ __('Total: :count', ['count' => $employees->total()]) }}</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Job title') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->user?->name ?? '—' }}</td>
                            <td>{{ $employee->user?->email ?? '—' }}</td>
                            <td>{{ $employee->job_title ?? '—' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.employees.show', $employee) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    @can('employees.update')
                                        <a href="{{ route('admin.employees.edit', $employee) }}"
                                            class="btn btn-sm btn-icon btn-outline-secondary">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                    @endcan
                                    @can('employees.delete')
                                        <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}"
                                            onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                {{ __('No employees found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $employees->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
