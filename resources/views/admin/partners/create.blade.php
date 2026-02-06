@extends('layouts.admin')

@section('title', __('Add partner'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Add partner') }}</h4>
            <p class="text-muted mb-0">{{ __('Create a new partner profile.') }}</p>
        </div>
        <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to partners') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.partners.store') }}">
                @include('admin.partners._form', ['submitLabel' => __('Create')])
            </form>
        </div>
    </div>
@endsection
