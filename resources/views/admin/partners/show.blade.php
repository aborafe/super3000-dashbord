@extends('layouts.admin')

@section('title', __('Partner details'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ $partner->name }}</h4>
            <p class="text-muted mb-0">{{ $partner->phone }}</p>
        </div>
        <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to list') }}
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Contact') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-4 text-muted">{{ __('Email') }}</dt>
                        <dd class="col-8">{{ $partner->email ?: '—' }}</dd>
                        <dt class="col-4 text-muted">{{ __('Address') }}</dt>
                        <dd class="col-8">{{ $partner->address ?: '—' }}</dd>
                        <dt class="col-4 text-muted">{{ __('Type') }}</dt>
                        <dd class="col-8">{{ ucfirst($partner->role_type) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Stats') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted">{{ __('Orders count') }}</dt>
                        <dd class="col-6">{{ $partner->orders->count() }}</dd>
                        <dt class="col-6 text-muted">{{ __('Debts count') }}</dt>
                        <dd class="col-6">{{ $partner->debts->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($partner->notes)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Notes') }}</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $partner->notes }}</p>
            </div>
        </div>
    @endif
@endsection
