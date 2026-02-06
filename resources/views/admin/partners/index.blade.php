@extends('layouts.admin')

@section('title', __('Partners'))

@section('content')
    @include('admin.components.flash')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">{{ __('Partners') }}</h4>
            <p class="text-muted mb-0">{{ __('Manage partner accounts.') }}</p>
        </div>

        @can('partners.create')
            <a href="{{ route('admin.partners.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>{{ __('Add partner') }}
            </a>
        @endcan
    </div>

    <div class="card mb-4">
        <h5 class="card-header">{{ __('Filters') }}</h5>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.partners.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            class="form-control" placeholder="{{ __('Name or phone') }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="role_type" class="form-select">
                            <option value="">{{ __('All') }}</option>
                            <option value="wholesale" @selected(($filters['role_type'] ?? null) === 'wholesale')>
                                {{ __('Wholesale') }}
                            </option>
                            <option value="retail" @selected(($filters['role_type'] ?? null) === 'retail')>
                                {{ __('Retail') }}
                            </option>
                            <option value="both" @selected(($filters['role_type'] ?? null) === 'both')>
                                {{ __('Both') }}
                            </option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter me-1"></i>{{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('Partners') }}</h5>
            <span class="text-muted small">{{ __('Total: :count', ['count' => $partners->total()]) }}</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($partners as $partner)
                        <tr>
                            <td>{{ $partner->name }}</td>
                            <td>{{ $partner->phone }}</td>
                            <td>
                                <span class="badge bg-label-primary">{{ ucfirst($partner->role_type) }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.partners.show', $partner) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    @can('partners.update')
                                        <a href="{{ route('admin.partners.edit', $partner) }}"
                                            class="btn btn-sm btn-icon btn-outline-secondary">
                                            <i class="bx bx-edit-alt"></i>
                                        </a>
                                    @endcan
                                    @can('partners.delete')
                                        <form method="POST" action="{{ route('admin.partners.destroy', $partner) }}"
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
                                {{ __('No partners found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $partners->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
