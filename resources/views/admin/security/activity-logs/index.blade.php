@extends('layouts.admin')

@section('title', __('Activity Logs'))

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold py-3 mb-0">{{ __('Activity Logs') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Activity Logs') }}</li>
          </ol>
        </nav>
      </div>
    </div>

    @include('admin.components.table-stats-strip')

    <div class="card">
      <div class="card-body">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Action') }}</th>
                <th>{{ __('Entity') }}</th>
                <th>{{ __('Entity ID') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Meta') }}</th>
                <th>{{ __('Date') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs as $log)
                <tr>
                  <td>{{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->action))) }}</td>
                  <td>{{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->entity_type))) }}</td>
                  <td>{{ $log->entity_id }}</td>
                  <td>
                    @if($log->user)
                      <a href="{{ route('admin.users.show', $log->user) }}" class="text-body">{{ $log->user->name }}</a>
                    @else
                      <span>{{ __('System') }}</span>
                    @endif
                  </td>
                  <td>{{ $log->meta ? json_encode($log->meta) : '-' }}</td>
                  <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted">{{ __('No activity logs.') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $logs->links('pagination::bootstrap-5') }}
        </div>
      </div>
    </div>
  </div>
@endsection
