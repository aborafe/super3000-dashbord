@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('content')
    @include('admin.components.flash')

    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-primary mb-3">{{ __('Welcome back!') }}</h5>
                    <p class="mb-3">
                        {{ __('You are logged in as :name (:email).', ['name' => auth()->user()->name, 'email' => auth()->user()->email]) }}
                    </p>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary btn-sm">
                        {{ __('Go to products') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">{{ __('Quick actions') }}</h6>
                    <div class="d-grid gap-2">
                        @can('orders.view')
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-sm">
                                {{ __('View orders') }}
                            </a>
                        @endcan
                        @can('employees.view')
                            <a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                                {{ __('Manage employees') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

