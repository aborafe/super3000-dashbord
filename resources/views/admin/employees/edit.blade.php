@extends('layouts.admin')

@section('title', __('Edit employee'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Edit employee') }}</h4>
            <p class="text-muted mb-0">{{ __('Update employee details.') }}</p>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to employees') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
                @method('PUT')
                @include('admin.employees._form', ['submitLabel' => __('Update'), 'employee' => $employee])
            </form>
        </div>
    </div>
@endsection
