@extends('layouts.admin')

@section('title', __('Add employee'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Add employee') }}</h4>
            <p class="text-muted mb-0">{{ __('Create a new team member.') }}</p>
        </div>
        <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to employees') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.employees.store') }}">
                @include('admin.employees._form', ['submitLabel' => __('Create')])
            </form>
        </div>
    </div>
@endsection
