@extends('layouts.admin')

@section('title', __('Edit partner'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Edit partner') }}</h4>
            <p class="text-muted mb-0">{{ __('Update partner details.') }}</p>
        </div>
        <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary">
            {{ __('Back to partners') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.partners.update', $partner) }}">
                @method('PUT')
                @include('admin.partners._form', ['submitLabel' => __('Update'), 'partner' => $partner])
            </form>
        </div>
    </div>
@endsection
