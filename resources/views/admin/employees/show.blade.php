@extends('layouts.admin')

@section('title', __('Employee details'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ $employee->user?->name ?? __('Employee') }}</h4>
            <p class="text-muted mb-0">{{ $employee->user?->email ?? '—' }}</p>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to list') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-4 text-muted">{{ __('Job title') }}</dt>
                <dd class="col-8">{{ $employee->job_title ?: '—' }}</dd>
                <dt class="col-4 text-muted">{{ __('Phone') }}</dt>
                <dd class="col-8">{{ $employee->phone ?: '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
