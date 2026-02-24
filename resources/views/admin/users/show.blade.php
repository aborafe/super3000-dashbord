@extends('layouts.admin')

@section('title', __('User Profile'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    @php $primaryRole = $user->roles->first()?->name ?? __('Staff'); @endphp
    @php $avatarInitial = $user->name ? (function_exists('mb_substr') ? mb_substr($user->name, 0, 1) : substr($user->name, 0, 1)) : '?'; @endphp
    @php $lastActivity = $activityLogs->first()?->created_at?->diffForHumans() ?? __('No activity yet'); @endphp
    @php $securityTabActive = $errors->has('password') || session('active_tab') === 'security'; @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <div class="nav-align-top">
                    <ul class="nav nav-pills flex-column flex-md-row mb-4 gap-md-0 gap-2" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link {{ $securityTabActive ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button" role="tab">
                                <i class="icon-base bx bx-user icon-sm me-1_5"></i> {{ __('Account') }}
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $securityTabActive ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">
                                <i class="icon-base bx bx-lock icon-sm me-1_5"></i> {{ __('Security') }}
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notifications" type="button" role="tab">
                                <i class="icon-base bx bx-bell icon-sm me-1_5"></i> {{ __('Notifications') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-content p-0">
            <div class="tab-pane fade {{ $securityTabActive ? '' : 'show active' }}" id="tab-profile" role="tabpanel">
                <div class="row">
                    <div class="col-xl-4">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="avatar avatar-xl mb-3 mx-auto">
                                    <span class="avatar-initial rounded-circle bg-label-primary fs-3">{{ $avatarInitial }}</span>
                                </div>
                                <h5 class="mb-1">{{ $user->name }}</h5>
                                <span class="badge bg-label-primary">{{ __(ucfirst((string) $primaryRole)) }}</span>
                                <div class="d-flex justify-content-center gap-4 my-4">
                                    <div class="text-center">
                                        <h5 class="mb-0">{{ number_format($activityLogs->count()) }}</h5>
                                        <small class="text-muted">{{ __('Activities') }}</small>
                                    </div>
                                    <div class="text-center">
                                        <h5 class="mb-0">{{ number_format(count($effectivePermissionNames)) }}</h5>
                                        <small class="text-muted">{{ __('Effective Permissions') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-2">
                                    @can('users.update')
                                        <a href="{{ route('admin.security.users.edit', ['locale' => $locale, 'user' => $user]) }}" class="btn btn-primary">
                                            {{ __('Edit') }}
                                        </a>
                                    @endcan
                                    <a href="{{ route('admin.users.index', ['locale' => $locale]) }}" class="btn btn-outline-secondary">
                                        {{ __('Back') }}
                                    </a>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <h6 class="text-uppercase text-muted mb-3">{{ __('Details') }}</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><span class="fw-medium">{{ __('Username') }}:</span> {{ $user->name }}</li>
                                    <li class="mb-2"><span class="fw-medium">{{ __('Email') }}:</span> {{ $user->email }}</li>
                                    <li class="mb-2"><span class="fw-medium">{{ __('Status') }}:</span> {{ __('Active') }}</li>
                                    <li class="mb-2"><span class="fw-medium">{{ __('Role') }}:</span> {{ __(ucfirst((string) $primaryRole)) }}</li>
                                    <li class="mb-0"><span class="fw-medium">{{ __('Last Activity') }}:</span> {{ $lastActivity }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="mb-3">{{ __('Roles') }}</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @forelse ($user->roles as $role)
                                        <span class="badge bg-label-primary">{{ __(ucfirst($role->name)) }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('No roles assigned') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="mb-3">{{ __('Permissions Snapshot') }}</h6>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>{{ __('From roles') }}</span>
                                    <span class="badge bg-label-primary">{{ count($rolePermissionNames) }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>{{ __('Direct grants') }}</span>
                                    <span class="badge bg-label-success">{{ count($directPermissionNames) }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>{{ __('Blocked') }}</span>
                                    <span class="badge bg-label-danger">{{ count($deniedPermissionNames) }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span>{{ __('Effective total') }}</span>
                                    <span class="badge bg-label-info">{{ count($effectivePermissionNames) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="mb-0">{{ __('Recent Activity') }}</h5>
                                <span class="badge bg-label-primary">{{ $activityLogs->count() }}</span>
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
                                        @forelse ($activityLogs as $log)
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

                        <div class="card mb-4">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="mb-0">{{ __('Permissions Overview') }}</h5>
                                <button class="btn btn-sm btn-outline-secondary">{{ __('View') }}</button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="mb-2">{{ __('Role Permissions') }}</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($rolePermissionNames as $permission)
                                            <span class="badge bg-label-primary">{{ $permission }}</span>
                                        @empty
                                            <span class="text-muted">{{ __('No role permissions') }}</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="mb-2">{{ __('Direct Grants') }}</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($directPermissionNames as $permission)
                                            <span class="badge bg-label-success">{{ $permission }}</span>
                                        @empty
                                            <span class="text-muted">{{ __('No direct grants') }}</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <h6 class="mb-2">{{ __('Blocked For This User') }}</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($deniedPermissionNames as $permission)
                                            <span class="badge bg-label-danger">{{ $permission }}</span>
                                        @empty
                                            <span class="text-muted">{{ __('No blocked permissions') }}</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="mb-0">{{ __('Recent Notifications') }}</h5>
                                <span class="badge bg-label-secondary">{{ $recentNotifications->count() }}</span>
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
                                        @forelse ($recentNotifications as $notification)
                                            @php $payload = localized_notification_payload(is_array($notification->data) ? $notification->data : [], $notification->type); @endphp
                                            <tr>
                                                <td>{{ (string) ($payload['title'] ?? __(\Illuminate\Support\Str::headline(class_basename($notification->type)))) }}</td>
                                                <td>{{ (string) ($payload['message'] ?? '-') }}</td>
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

            <div class="tab-pane fade {{ $securityTabActive ? 'show active' : '' }}" id="tab-security" role="tabpanel">
                <div class="row">
                    <div class="col-xl-4">
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="avatar avatar-xl mb-3 mx-auto">
                                    <span class="avatar-initial rounded-circle bg-label-primary fs-3">{{ $avatarInitial }}</span>
                                </div>
                                <h5 class="mb-1">{{ $user->name }}</h5>
                                <span class="badge bg-label-primary">{{ __(ucfirst((string) $primaryRole)) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">{{ __('Change Password') }}</h5>
                                @can('users.update')
                                    <form method="POST" action="{{ route('admin.security.users.password', ['locale' => $locale, 'user' => $user]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('New Password') }}</label>
                                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Confirm Password') }}</label>
                                                <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                                            </div>
                                        </div>
                                        <button class="btn btn-primary mt-3" type="submit">{{ __('Update Password') }}</button>
                                    </form>
                                @else
                                    <div class="alert alert-warning mb-0">{{ __('You are not allowed to change this password.') }}</div>
                                @endcan
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">{{ __('Permission Security') }}</h5>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span>{{ __('Blocked permissions') }}</span>
                                    <span class="badge bg-label-danger">{{ count($deniedPermissionNames) }}</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($deniedPermissionNames as $permission)
                                        <span class="badge bg-label-danger">{{ $permission }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('No blocked permissions') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">{{ __('Recent Security Events') }}</h5>
                                <ul class="order-timeline mb-0">
                                    @forelse ($activityLogs->take(6) as $log)
                                        <li class="order-timeline-item">
                                            <span class="timeline-point timeline-point-primary"></span>
                                            <div class="d-flex justify-content-between flex-wrap">
                                                <div>
                                                    <h6 class="mb-0">{{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->action))) }} {{ __(\Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $log->entity_type))) }}</h6>
                                                    <small class="text-muted">{{ (string) ($log->meta['name'] ?? __('System update')) }}</small>
                                                </div>
                                                <small class="text-muted">{{ $log->created_at?->diffForHumans() }}</small>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="text-muted">{{ __('No events yet.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">{{ __('Send notification to this user') }}</h5>
                        <form method="POST" action="{{ route('admin.notifications.send', ['locale' => $locale]) }}">
                            @csrf
                            <input type="hidden" name="recipients[]" value="user:{{ $user->id }}">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Title') }}</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Message') }}</label>
                                <textarea name="message" rows="3" class="form-control @error('message') is-invalid @enderror"></textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Send') }}</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-muted">{{ __('Notification history is shown in Account tab.') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

