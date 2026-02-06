@php
    $user = auth()->user();
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
    $recentNotifications = $user?->notifications()->latest()->limit(5)->get() ?? collect();
@endphp

<nav
    class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base bx bx-menu icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center me-auto">
            <div class="nav-item d-flex align-items-center">
                <span class="w-px-22 h-px-22">
                    <i class="icon-base bx bx-search icon-md"></i>
                </span>
                <input
                    type="text"
                    class="form-control border-0 shadow-none ps-1 ps-sm-2 d-md-block d-none"
                    placeholder="{{ __('Search...') }}"
                    aria-label="{{ __('Search...') }}" />
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-bell icon-md"></i>
                    @if ($unreadCount > 0)
                        <span class="badge bg-danger rounded-pill badge-notifications">{{ $unreadCount }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px;">
                    <li>
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>{{ __('Notifications') }}</span>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-link p-0">
                                        {{ __('Mark all read') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                    <li><div class="dropdown-divider my-1"></div></li>

                    @forelse ($recentNotifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $title = $data['title'] ?? __('Notification');
                            $message = $data['message'] ?? '';
                            $routeName = $data['route'] ?? null;
                            $routeParams = $data['route_params'] ?? [];
                            $hasParams = collect($routeParams)->every(fn ($value) => ! is_null($value));
                            $url = $routeName && $hasParams ? route($routeName, $routeParams) : ($data['url'] ?? null);
                        @endphp
                        <li>
                            <div class="dropdown-item d-flex flex-column gap-1 {{ $notification->read_at ? '' : 'bg-label-primary' }}">
                                <div class="fw-semibold text-truncate">{{ $title }}</div>
                                @if ($message)
                                    <small class="text-body-secondary text-truncate">{{ $message }}</small>
                                @endif
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <small class="text-body-secondary">{{ $notification->created_at?->diffForHumans() }}</small>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($url)
                                            <a href="{{ $url }}" class="text-primary small">{{ __('View') }}</a>
                                        @endif
                                        @if (! $notification->read_at)
                                            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link p-0 text-secondary">
                                                    {{ __('Mark read') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>
                            <div class="dropdown-item text-center text-body-secondary">
                                {{ __('No notifications.') }}
                            </div>
                        </li>
                    @endforelse
                </ul>
            </li>

            @include('admin.partials.settings-dropdown')
        </ul>
    </div>
</nav>
