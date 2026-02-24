@php $locale = app()->getLocale(); @endphp
@php $themeClass = ($appearanceTheme ?? 'light') === 'dark' ? 'dark-style' : 'light-style'; @endphp
@php $dir = $locale === 'ar' || !empty($appearanceRtl) ? 'rtl' : 'ltr'; @endphp
@php $currentRoute = request()->route(); @endphp
@php $routeName = $currentRoute?->getName(); @endphp
@php $routeParams = $currentRoute?->parameters() ?? []; @endphp
@php $queryParams = request()->query(); @endphp
@php $langEnUrl = $routeName ? route($routeName, array_merge($routeParams, $queryParams, ['locale' => 'en'])) : route('admin.dashboard', ['locale' => 'en']); @endphp
@php $langArUrl = $routeName ? route($routeName, array_merge($routeParams, $queryParams, ['locale' => 'ar'])) : route('admin.dashboard', ['locale' => 'ar']); @endphp
@php $authUser = auth()->user(); @endphp
@php $headerNotifications = $authUser ? $authUser->notifications()->latest()->limit(5)->get() : collect(); @endphp
@php $unreadNotificationsCount = $authUser ? $authUser->unreadNotifications()->count() : 0; @endphp
@php $broadcastDriver = (string) config('broadcasting.default', 'null'); @endphp
@php $broadcastConnection = (array) config("broadcasting.connections.{$broadcastDriver}", []); @endphp
@php $broadcastOptions = (array) ($broadcastConnection['options'] ?? []); @endphp
@php $realtimeEnabled = in_array($broadcastDriver, ['reverb', 'pusher'], true); @endphp
@php $realtimeHost = (string) ($broadcastOptions['host'] ?? request()->getHost()); @endphp
@php $realtimePort = (int) ($broadcastOptions['port'] ?? 80); @endphp
@php $realtimeScheme = (string) ($broadcastOptions['scheme'] ?? 'http'); @endphp
@php $realtimeKey = (string) ($broadcastConnection['key'] ?? ''); @endphp
@php $realtimeChannel = $authUser ? 'user.' . $authUser->getKey() : ''; @endphp
@php $realtimeAuthEndpoint = url('/broadcasting/auth'); @endphp
<!doctype html>

<html lang="{{ $locale }}" dir="{{ $dir }}" class="layout-menu-fixed layout-compact {{ $themeClass }}"
    data-assets-path="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/"
    data-i18n-path="{{ asset('i18n') }}" data-default-lang="ar" data-template="vertical-menu-template-free">

<head>
    <script>
        (function() {
            var root = document.documentElement;
            var storedTheme = null;

            try {
                storedTheme = window.localStorage.getItem('theme');
            } catch (error) {
                storedTheme = null;
            }

            var systemPrefersDark = false;
            try {
                systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            } catch (error) {
                systemPrefersDark = false;
            }

            var theme = storedTheme === 'dark' || storedTheme === 'light' ?
                storedTheme :
                (systemPrefersDark ? 'dark' : 'light');

            root.setAttribute('data-theme', theme);
            root.classList.remove('light-style', 'dark-style');
            root.classList.add(theme === 'dark' ? 'dark-style' : 'light-style');
            root.style.colorScheme = theme;
        })();
    </script>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('Admin'))</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon"
        href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <link rel="stylesheet"
        href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/css/demo.css" />
    <link rel="stylesheet" href="{{ asset('theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('loading-ui.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-overrides.css') }}" />
    <link rel="stylesheet" href="{{ asset('responsive-fixes.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/responsive-rtl-fixes.css') }}">

    <!-- Vendors CSS -->

    <link rel="stylesheet"
        href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <link rel="stylesheet"
        href="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->

    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="{{ route('admin.dashboard', ['locale' => $locale]) }}" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <span class="text-primary">
                                <svg width="25" viewBox="0 0 25 42" version="1.1"
                                    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <defs>
                                        <path
                                            d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z"
                                            id="path-1"></path>
                                        <path
                                            d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z"
                                            id="path-3"></path>
                                        <path
                                            d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z"
                                            id="path-4"></path>
                                        <path
                                            d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z"
                                            id="path-5"></path>
                                    </defs>
                                    <g id="g-app-brand" stroke="none" stroke-width="1" fill="none"
                                        fill-rule="evenodd">
                                        <g id="Brand-Logo" transform="translate(-27.000000, -15.000000)">
                                            <g id="Icon" transform="translate(27.000000, 15.000000)">
                                                <g id="Mask" transform="translate(0.000000, 8.000000)">
                                                    <mask id="mask-2" fill="white">
                                                        <use xlink:href="#path-1"></use>
                                                    </mask>
                                                    <use fill="currentColor" xlink:href="#path-1"></use>
                                                    <g id="Path-3" mask="url(#mask-2)">
                                                        <use fill="currentColor" xlink:href="#path-3"></use>
                                                        <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3">
                                                        </use>
                                                    </g>
                                                    <g id="Path-4" mask="url(#mask-2)">
                                                        <use fill="currentColor" xlink:href="#path-4"></use>
                                                        <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4">
                                                        </use>
                                                    </g>
                                                </g>
                                                <g id="Triangle"
                                                    transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) ">
                                                    <use fill="currentColor" xlink:href="#path-5"></use>
                                                    <use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use>
                                                </g>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </span>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold ms-2">{{ $siteName ?? 'Super3000' }}</span>
                    </a>

                    <button type="button"
                        class="layout-menu-toggle text-large ms-auto d-xl-none border-0 bg-transparent p-0"
                        data-menu-toggle="layout">
                        <i class="bx bx-chevron-left d-block align-middle"></i>
                    </button>
                    <button type="button"
                        class="btn btn-icon rounded-circle ms-2 d-none d-xl-inline-flex menu-pin-btn"
                        data-menu-pin-toggle aria-label="{{ __('Toggle menu pin') }}">
                        <i class="bx bx-lock-open"></i>
                    </button>
                </div>

                <div class="menu-divider mt-0"></div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    @can('dashboard.view')
                        <li class="menu-item{{ request()->routeIs('admin.dashboard') ? ' active' : '' }}">
                            <a href="{{ route('admin.dashboard', ['locale' => $locale]) }}" class="menu-link">
                                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                                <div class="text-truncate" data-i18n="Dashboard">{{ __('Dashboard') }}</div>
                            </a>
                        </li>
                    @endcan

                    @canany(['orders.view', 'payments.view', 'customers.view'])
                        <li
                            class="menu-item{{ request()->routeIs('admin.orders.*', 'admin.sales.payments.*', 'admin.sales.customers.*') ? ' open active' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" data-toggle="submenu">
                                <i class="menu-icon tf-icons bx bx-cart"></i>
                                <div class="text-truncate" data-i18n="Sales">{{ __('Sales') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @can('orders.view')
                                    <li class="menu-item{{ request()->routeIs('admin.orders.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.orders.index', ['locale' => $locale]) }}" class="menu-link">
                                            <div class="text-truncate" data-i18n="Orders">{{ __('Orders') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('payments.view')
                                    <li class="menu-item{{ request()->routeIs('admin.sales.payments.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.sales.payments.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Payments">{{ __('Payments') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('customers.view')
                                    <li class="menu-item{{ request()->routeIs('admin.sales.customers.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.sales.customers.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Customers">{{ __('Customers') }}</div>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    @canany(['products.view', 'categories.view', 'inventory.view'])
                        <li
                            class="menu-item{{ request()->routeIs('admin.products.*', 'admin.catalog.categories.*', 'admin.catalog.inventory.*') ? ' open active' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" data-toggle="submenu">
                                <i class="menu-icon tf-icons bx bx-package"></i>
                                <div class="text-truncate" data-i18n="Catalog">{{ __('Catalog') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @can('products.view')
                                    <li class="menu-item{{ request()->routeIs('admin.products.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.products.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Products">{{ __('Products') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('categories.view')
                                    <li
                                        class="menu-item{{ request()->routeIs('admin.catalog.categories.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.catalog.categories.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Categories">{{ __('Categories') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('inventory.view')
                                    <li
                                        class="menu-item{{ request()->routeIs('admin.catalog.inventory.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.catalog.inventory.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Inventory">{{ __('Inventory') }}</div>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    @canany(['warehouses.view', 'reports.view'])
                        <li
                            class="menu-item{{ request()->routeIs('admin.operations.warehouses.*', 'admin.operations.reports.*') ? ' open active' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" data-toggle="submenu">
                                <i class="menu-icon tf-icons bx bx-buildings"></i>
                                <div class="text-truncate" data-i18n="Operations">{{ __('Operations') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @can('warehouses.view')
                                    <li
                                        class="menu-item{{ request()->routeIs('admin.operations.warehouses.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.operations.warehouses.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Warehouses">{{ __('Warehouses') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('reports.view')
                                    <li
                                        class="menu-item{{ request()->routeIs('admin.operations.reports.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.operations.reports.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Reports">{{ __('Reports') }}</div>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    @canany(['users.view', 'roles.view', 'activity_logs.view'])
                        <li
                            class="menu-item{{ request()->routeIs('admin.users.*', 'admin.security.roles.*', 'admin.security.activity.*') ? ' open active' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" data-toggle="submenu">
                                <i class="menu-icon tf-icons bx bx-shield"></i>
                                <div class="text-truncate" data-i18n="Users & Security">{{ __('Users & Security') }}
                                </div>
                            </a>
                            <ul class="menu-sub">
                                @can('users.view')
                                    <li class="menu-item{{ request()->routeIs('admin.users.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.users.index', ['locale' => $locale]) }}" class="menu-link">
                                            <div class="text-truncate" data-i18n="Users">{{ __('Users') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('roles.view')
                                    <li class="menu-item{{ request()->routeIs('admin.security.roles.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.security.roles.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Roles & Permissions">
                                                {{ __('Roles & Permissions') }}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('activity_logs.view')
                                    <li
                                        class="menu-item{{ request()->routeIs('admin.security.activity.*') ? ' active' : '' }}">
                                        <a href="{{ route('admin.security.activity.index', ['locale' => $locale]) }}"
                                            class="menu-link">
                                            <div class="text-truncate" data-i18n="Activity Logs">{{ __('Activity Logs') }}
                                            </div>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    @can('settings.manage')
                        <li class="menu-item{{ request()->routeIs('admin.settings.*') ? ' open active' : '' }}">
                            <a href="#" class="menu-link menu-toggle" role="button" data-toggle="submenu">
                                <i class="menu-icon tf-icons bx bx-cog"></i>
                                <div class="text-truncate" data-i18n="Settings">{{ __('Settings') }}</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item{{ request()->routeIs('admin.settings.general') ? ' active' : '' }}">
                                    <a href="{{ route('admin.settings.general', ['locale' => $locale]) }}"
                                        class="menu-link">
                                        <div class="text-truncate" data-i18n="General Settings">
                                            {{ __('General Settings') }}</div>
                                    </a>
                                </li>
                                <li
                                    class="menu-item{{ request()->routeIs('admin.settings.appearance') ? ' active' : '' }}">
                                    <a href="{{ route('admin.settings.appearance', ['locale' => $locale]) }}"
                                        class="menu-link">
                                        <div class="text-truncate" data-i18n="Appearance">{{ __('Appearance') }}</div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcan
                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->

                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                    id="layout-navbar">
                    <div class="navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <button type="button"
                            class="layout-menu-toggle nav-item nav-link px-0 me-xl-6 border-0 bg-transparent"
                            data-menu-toggle="layout">
                            <i class="icon-base bx bx-menu icon-md"></i>
                        </button>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center me-auto">
                            <div class="nav-item d-flex align-items-center">
                                <span class="w-px-22 h-px-22"><i class="icon-base bx bx-search icon-md"></i></span>
                                <input type="text" id="navbar-search" name="navbar_search"
                                    class="form-control border-0 shadow-none ps-1 ps-sm-2 d-md-block d-none"
                                    placeholder="{{ __('Search...') }}" aria-label="{{ __('Search...') }}"
                                    data-i18n-attr="placeholder:Search...,aria-label:Search..." />
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <i class="icon-base bx bx-globe icon-md"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="{{ $langArUrl }}"
                                            data-lang="ar">
                                            <span data-i18n="Arabic">{{ __('Arabic') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="{{ $langEnUrl }}"
                                            data-lang="en">
                                            <span data-i18n="English">{{ __('English') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown me-2">
                                <a id="theme-toggle" class="nav-link dropdown-toggle hide-arrow p-0"
                                    href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <i class="icon-base bx bx-sun icon-md" data-theme-icon></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);"
                                            data-theme="light">
                                            <i class="icon-base bx bx-sun icon-md me-2"></i>
                                            <span data-i18n="Light">{{ __('Light') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);"
                                            data-theme="dark">
                                            <i class="icon-base bx bx-moon icon-md me-2"></i>
                                            <span data-i18n="Dark">{{ __('Dark') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="javascript:void(0);"
                                            data-theme="system">
                                            <i class="icon-base bx bx-desktop icon-md me-2"></i>
                                            <span data-i18n="System">{{ __('System') }}</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            @can('notifications.view')
                                <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <i class="icon-base bx bx-bell icon-md"></i>
                                    <span
                                        class="badge bg-danger rounded-pill badge-notifications {{ $unreadNotificationsCount > 0 ? '' : 'd-none' }}">
                                        {{ $unreadNotificationsCount }}
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end p-0">
                                    <li class="dropdown-menu-header border-bottom">
                                        <div class="dropdown-header d-flex align-items-center justify-content-between">
                                            <h5 class="text-body mb-0 me-1" data-i18n="Notifications">
                                                {{ __('Notifications') }}</h5>
                                            <span class="badge bg-label-primary rounded-pill">
                                                <span id="notifications-count">{{ $unreadNotificationsCount }}</span>
                                                <span data-i18n="New">{{ __('New') }}</span>
                                            </span>
                                            <form method="POST"
                                                action="{{ route('admin.notifications.read-all', ['locale' => $locale]) }}"
                                                class="m-0 {{ $unreadNotificationsCount > 0 ? '' : 'd-none' }}"
                                                id="notifications-mark-all-form">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="dropdown-notifications-archive border-0 bg-transparent p-0"
                                                    id="notifications-mark-all" title="{{ __('Mark all as read') }}">
                                                    <span class="icon-base bx bx-envelope-open"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                    <li class="dropdown-notifications-list" id="notifications-list"
                                        data-live-url="{{ route('admin.notifications.live', ['locale' => $locale]) }}"
                                        data-index-url="{{ route('admin.notifications.index', ['locale' => $locale]) }}"
                                        data-empty-text="{{ __('No notifications yet.') }}"
                                        data-mark-read-title="{{ __('Mark as read') }}"
                                        data-poll-interval-ms="5000"
                                        data-realtime-enabled="{{ $realtimeEnabled && $realtimeKey !== '' && $realtimeChannel !== '' ? '1' : '0' }}"
                                        data-realtime-broadcaster="{{ $broadcastDriver }}"
                                        data-realtime-key="{{ $realtimeKey }}"
                                        data-realtime-host="{{ $realtimeHost }}"
                                        data-realtime-port="{{ $realtimePort }}"
                                        data-realtime-scheme="{{ $realtimeScheme }}"
                                        data-realtime-auth-endpoint="{{ $realtimeAuthEndpoint }}"
                                        data-realtime-channel="{{ $realtimeChannel }}">
                                        <ul class="list-group list-group-flush overflow-auto"
                                            style="max-height:320px;">
                                            @forelse ($headerNotifications as $notification)
                                                @php $payload = localized_notification_payload(is_array($notification->data) ? $notification->data : [], $notification->type); @endphp
                                                @php $isRead = $notification->read_at !== null; @endphp
                                                @php $title = (string) ($payload['title'] ?? __(\Illuminate\Support\Str::headline(class_basename($notification->type)))); @endphp
                                                @php $message = (string) ($payload['message'] ?? ''); @endphp
                                                <li
                                                    class="list-group-item list-group-item-action dropdown-notifications-item {{ $isRead ? 'is-read' : '' }}">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="avatar">
                                                                <span
                                                                    class="avatar-initial rounded-circle {{ $isRead ? 'bg-label-secondary' : 'bg-label-primary' }}">
                                                                    <i class="icon-base bx bx-bell"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <a href="{{ route('admin.notifications.show', ['locale' => $locale, 'notification' => $notification->id]) }}"
                                                            class="flex-grow-1 text-body text-decoration-none">
                                                            <h6 class="mb-1">{{ $title }}</h6>
                                                            <small
                                                                class="text-muted d-block">{{ $message }}</small>
                                                            <div class="small text-muted">
                                                                {{ optional($notification->created_at)->diffForHumans() }}
                                                            </div>
                                                        </a>
                                                        <div class="flex-shrink-0 dropdown-notifications-actions ms-2">
                                                            @if (!$isRead)
                                                                <form method="POST"
                                                                    action="{{ route('admin.notifications.read', ['locale' => $locale, 'notification' => $notification->id]) }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit"
                                                                        class="dropdown-notifications-read border-0 bg-transparent p-0"
                                                                        title="{{ __('Mark as read') }}">
                                                                        <span
                                                                            class="badge rounded-pill bg-primary p-1 notification-dot"></span>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </li>
                                            @empty
                                                <li class="list-group-item py-3 text-center text-muted">
                                                    {{ __('No notifications yet.') }}
                                                </li>
                                            @endforelse
                                        </ul>
                                    </li>
                                    <li class="dropdown-menu-footer border-top">
                                        <a href="{{ route('admin.notifications.index', ['locale' => $locale]) }}"
                                            class="dropdown-item d-flex justify-content-center p-3"
                                            title="{{ __('View all notifications') }}">
                                            <button type="button" class="btn btn-primary w-100 border"
                                                style="border-color:rgba(0,0,0,0.06);"
                                                aria-label="{{ __('View all notifications') }}">
                                                {{ __('View all notifications') }}
                                            </button>
                                        </a>
                                    </li>
                                </ul>
                                </li>
                            @endcan
                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);"
                                    data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/img/avatars/1.png"
                                            alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0);">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/img/avatars/1.png"
                                                            alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">{{ $authUser?->name }}</h6>
                                                    <small
                                                        class="text-body-secondary">{{ $authUser?->email }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.myprofile', ['locale' => $locale]) }}">
                                            <i class="icon-base bx bx-user icon-md me-3"></i><span
                                                data-i18n="My Profile">{{ __('My Profile') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.settings.general', ['locale' => $locale]) }}">
                                            <i class="icon-base bx bx-cog icon-md me-3"></i><span
                                                data-i18n="Settings">{{ __('Settings') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('admin.settings.appearance', ['locale' => $locale]) }}">
                                            <span class="d-flex align-items-center align-middle">
                                                <i
                                                    class="flex-shrink-0 icon-base bx bx-credit-card icon-md me-3"></i><span
                                                    class="flex-grow-1 align-middle"
                                                    data-i18n="Billing Plan">{{ __('Billing Plan') }}</span>
                                                <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center justify-content-between"
                                            href="javascript:void(0);" data-sfx-toggle aria-pressed="true">
                                            <span>
                                                <i class="icon-base bx bx-volume-full icon-md me-3"></i><span
                                                    data-i18n="Sound Effects">{{ __('Sound Effects') }}</span>
                                            </span>
                                            <span class="badge rounded-pill bg-label-primary" data-sfx-state>{{ __('On') }}</span>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    <li>
                                        <form method="POST"
                                            action="{{ route('admin.logout', ['locale' => $locale]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="dropdown-item border-0 bg-transparent w-100 text-start"
                                                data-sfx="success">
                                                <i class="icon-base bx bx-power-off icon-md me-3"></i><span
                                                    data-i18n="Log Out">{{ __('Log Out') }}</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    @if (session('success'))
                        <span class="d-none" data-flash-success="1" aria-hidden="true"></span>
                    @endif

                    {{-- show validation errors or general error message in a visible alert --}}
                    @if ($errors->any())
                        <div class="container-xxl pt-3">
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @elseif (session('error'))
                        <div class="container-xxl pt-3">
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    @yield('content')

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div
                                class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                <div class="mb-2 mb-md-0">
                                    ©
                                    <script>
                                        document.write(new Date().getFullYear());
                                    </script>
                                    , <span data-i18n="made with ❤️ by">{{ __('made with ❤️ by') }}</span>
                                    <a href="" class="footer-link">mohamed aborafe</a>
                                </div>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay" data-layout-overlay></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->

    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/jquery/jquery.js"></script>

    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/popper/popper.js"></script>
    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/js/bootstrap.js"></script>

    <script
        src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js">
    </script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="{{ asset('sneat-bootstrap-html-admin-template-free/assets') }}/vendor/libs/apex-charts/apexcharts.js">
    </script>

    @if ($realtimeEnabled)
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    @endif

    <!-- App JS -->
    <script type="module" src="{{ asset('js/app.js') }}"></script>

    @yield('page-scripts')

</body>

</html>

