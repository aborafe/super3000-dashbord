@extends('layouts.admin')

@section('title', __('Notifications'))

@section('content')
    @php $locale = app()->getLocale(); @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold py-3 mb-0" data-i18n="Notifications">{{ __('Notifications') }}</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', ['locale' => $locale]) }}"
                                data-i18n="Dashboard">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active" data-i18n="Notifications">{{ __('Notifications') }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                @if (($unreadCount ?? 0) > 0)
                    <form method="POST" action="{{ route('admin.notifications.read-all', ['locale' => $locale]) }}"
                        class="d-inline-block me-2">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            {{ __('Mark all as read') }}
                        </button>
                    </form>
                @endif
                @can('notifications.send')
                    <a href="{{ route('admin.notifications.compose', ['locale' => $locale]) }}"
                        class="btn btn-sm btn-primary">{{ __('Compose') }}</a>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <h5 class="mb-0" data-i18n="Notifications">{{ __('Notifications') }}</h5>
                    <span class="badge bg-label-primary">{{ __('Unread') }}: {{ (int) ($unreadCount ?? 0) }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse ($notifications as $notification)
                        @php $payload = is_array($notification->data) ? $notification->data : []; @endphp
                        @php $isRead = $notification->read_at !== null; @endphp
                        @php $title = (string) ($payload['title'] ?? __(\Illuminate\Support\Str::headline(class_basename($notification->type)))); @endphp
                        @php $message = (string) ($payload['message'] ?? ''); @endphp
                        @php $messagePreview = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', trim($message)) ?? '', 120, '...'); @endphp
                        <li class="list-group-item">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar">
                                    <span
                                        class="avatar-initial rounded-circle {{ $isRead ? 'bg-label-secondary' : 'bg-label-primary' }}">
                                        <i class="icon-base bx bx-bell"></i>
                                    </span>
                                </div>

                                <div class="flex-grow-1">
                                    <a href="{{ route('admin.notifications.show', ['locale' => $locale, 'notification' => $notification->id]) }}"
                                        class="text-body text-decoration-none">
                                        <h6 class="mb-1">{{ $title }}</h6>
                                        <small class="text-muted d-block">{{ $messagePreview }}</small>
                                        <small class="text-muted">{{ optional($notification->created_at)->diffForHumans() }}</small>
                                    </a>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    @if (!$isRead)
                                        <form method="POST"
                                            action="{{ route('admin.notifications.read', ['locale' => $locale, 'notification' => $notification->id]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                {{ __('Mark as read') }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-label-success">{{ __('Read') }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item py-4 text-center text-muted">{{ __('No notifications yet.') }}</li>
                    @endforelse
                </ul>
            </div>
            @if (method_exists($notifications, 'links'))
                <div class="card-footer">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

