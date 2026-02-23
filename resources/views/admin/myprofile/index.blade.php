@extends('layouts.admin')

@section('title', __('My Profile'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $primaryRole = $user?->roles?->first()?->name ?? __('Staff'); @endphp
    @php $avatarInitial = $user?->name ? (function_exists('mb_substr') ? mb_substr($user->name, 0, 1) : substr($user->name, 0, 1)) : '?'; @endphp
    @php $activityCount = (int) ($activityLogs?->count() ?? 0); @endphp
    @php $permissionsCount = (int) ($permissionsCount ?? 0); @endphp
    @php $unreadNotificationsCount = (int) ($unreadNotificationsCount ?? 0); @endphp
    @php $lastActivity = $activityLogs?->first()?->created_at?->diffForHumans() ?? __('No activity yet'); @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0" data-i18n="My Profile">{{ __('My Profile') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard', ['locale' => $locale]) }}"
                                data-i18n="Dashboard">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item active" data-i18n="My Profile">{{ __('My Profile') }}</li>
                    </ol>
                </nav>
            </div>
            @can('notifications.view')
                <a href="{{ route('admin.notifications.index', ['locale' => $locale]) }}" class="btn btn-outline-primary">
                    <i class="bx bx-bell me-1"></i>{{ __('Notifications') }}
                </a>
            @endcan
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="avatar avatar-xl mb-3 mx-auto">
                            @if (!empty($user?->avatar))
                                <img src="{{ $user->avatar }}" alt class="w-px-80 h-auto rounded-circle" />
                            @else
                                <span class="avatar-initial rounded-circle bg-label-primary fs-3">{{ $avatarInitial }}</span>
                            @endif
                        </div>
                        <h5 class="mb-1">{{ $user?->name ?? '-' }}</h5>
                        <span class="badge bg-label-primary">{{ __(ucfirst((string) $primaryRole)) }}</span>
                        <div class="d-flex justify-content-center gap-4 my-4">
                            <div class="text-center">
                                <h5 class="mb-0">{{ number_format($activityCount) }}</h5>
                                <small class="text-muted">{{ __('Activities') }}</small>
                            </div>
                            <div class="text-center">
                                <h5 class="mb-0">{{ number_format($permissionsCount) }}</h5>
                                <small class="text-muted">{{ __('Permissions') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <h6 class="text-uppercase text-muted mb-3">{{ __('Details') }}</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Username') }}:</span> {{ $user?->name ?? '-' }}
                            </li>
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Email') }}:</span> {{ $user?->email ?? '-' }}
                            </li>
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Role') }}:</span> {{ __(ucfirst((string) $primaryRole)) }}
                            </li>
                            <li class="mb-2">
                                <span class="fw-medium">{{ __('Unread') }}:</span> {{ number_format($unreadNotificationsCount) }}
                            </li>
                            <li class="mb-0">
                                <span class="fw-medium">{{ __('Last Activity') }}:</span> {{ $lastActivity }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="mb-3">{{ __('Roles') }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse ($user?->roles ?? collect() as $role)
                                <span class="badge bg-label-primary">{{ __(ucfirst((string) $role->name)) }}</span>
                            @empty
                                <span class="text-muted">{{ __('No roles assigned') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">{{ __('Recent Activity') }}</h5>
                        <span class="badge bg-label-primary">{{ $activityCount }}</span>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Action') }}</th>
                                    <th>{{ __('Entity') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('When') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activityLogs ?? collect() as $log)
                                    <tr>
                                        <td>
                                            <span class="badge bg-label-primary">
                                                {{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->action))) }}
                                            </span>
                                        </td>
                                        <td>{{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->entity_type))) }} #{{ $log->entity_id ?? '-' }}</td>
                                        <td>{{ (string) ($log->meta['name'] ?? __('System update')) }}</td>
                                        <td>{{ $log->created_at?->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('No activity yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">{{ __('Recent Notifications') }}</h5>
                        <span class="badge bg-label-secondary">{{ ($recentNotifications ?? collect())->count() }}</span>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Title') }}</th>
                                    <th>{{ __('Message') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentNotifications ?? collect() as $notification)
                                    @php $payload = is_array($notification->data) ? $notification->data : []; @endphp
                                    <tr>
                                        <td>{{ (string) ($payload['title'] ?? __(\Illuminate\Support\Str::headline(class_basename($notification->type)))) }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit((string) ($payload['message'] ?? '-'), 70, '...') }}</td>
                                        <td>
                                            <span class="badge {{ $notification->read_at ? 'bg-label-secondary' : 'bg-label-primary' }}">
                                                {{ $notification->read_at ? __('Read') : __('Unread') }}
                                            </span>
                                        </td>
                                        <td>{{ $notification->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">{{ __('No notifications yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

