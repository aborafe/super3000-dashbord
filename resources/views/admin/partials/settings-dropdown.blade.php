@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $targetLocale = $locale === 'ar' ? 'en' : 'ar';
    $segments = request()->segments();
    if (count($segments) && in_array($segments[0], ['en', 'ar'], true)) {
        $segments[0] = $targetLocale;
    } else {
        array_unshift($segments, $targetLocale);
    }
    $switchUrl = url(implode('/', $segments));
    if (request()->getQueryString()) {
        $switchUrl .= '?' . request()->getQueryString();
    }
    $name = trim($user?->name ?? 'User');
    $initials = collect(explode(' ', $name))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<li class="nav-item navbar-dropdown dropdown-user dropdown">
    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary">{{ $initials }}</span>
        </div>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="javascript:void(0);">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <div class="avatar avatar-online">
                            <span class="avatar-initial rounded-circle bg-label-primary">{{ $initials }}</span>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $name }}</h6>
                        <small class="text-body-secondary">{{ $user?->email ?? '' }}</small>
                    </div>
                </div>
            </a>
        </li>
        <li><div class="dropdown-divider my-1"></div></li>
        <li>
            <a class="dropdown-item" href="{{ $switchUrl }}">
                <i class="icon-base bx bx-globe icon-md me-3"></i>
                <span>{{ $targetLocale === 'ar' ? __('العربية') : __('English') }}</span>
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item d-flex align-items-center" data-theme-toggle>
                <i class="icon-base bx bx-moon icon-md me-3"></i>
                <span>{{ __('Toggle dark mode') }}</span>
            </button>
        </li>
        <li><div class="dropdown-divider my-1"></div></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">
                    <i class="icon-base bx bx-power-off icon-md me-3"></i>
                    <span>{{ __('Logout') }}</span>
                </button>
            </form>
        </li>
    </ul>
</li>
