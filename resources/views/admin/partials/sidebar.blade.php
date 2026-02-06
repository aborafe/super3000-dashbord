<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <span class="text-primary fw-bold">S3</span>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">
                {{ config('app.name', 'Super3000') }}
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div class="text-truncate" data-i18n="Dashboard">{{ __('Dashboard') }}</div>
            </a>
        </li>

        @canany(['products.view', 'orders.view'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">{{ __('eCommerce') }}</span>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.products.*') || request()->routeIs('admin.orders.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-store"></i>
                    <div class="text-truncate" data-i18n="eCommerce">{{ __('eCommerce') }}</div>
                </a>
                <ul class="menu-sub">
                    @can('products.view')
                        <li class="menu-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.products.index') }}" class="menu-link">
                                <div class="text-truncate" data-i18n="Products">{{ __('Products') }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('orders.view')
                        <li class="menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.orders.index') }}" class="menu-link">
                                <div class="text-truncate" data-i18n="Orders">{{ __('Orders') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        @canany(['employees.view', 'partners.view'])
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">{{ __('Users') }}</span>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.employees.*') || request()->routeIs('admin.partners.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-group"></i>
                    <div class="text-truncate" data-i18n="Users">{{ __('Users') }}</div>
                </a>
                <ul class="menu-sub">
                    @can('employees.view')
                        <li class="menu-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.employees.index') }}" class="menu-link">
                                <div class="text-truncate" data-i18n="Employees">{{ __('Employees') }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('partners.view')
                        <li class="menu-item {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.partners.index') }}" class="menu-link">
                                <div class="text-truncate" data-i18n="Partners">{{ __('Partners') }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ __('Operations') }}</span>
        </li>

        @can('warehouses.view')
            <li class="menu-item {{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}">
                <a href="{{ route('admin.warehouses.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div class="text-truncate" data-i18n="Warehouses">{{ __('Warehouses') }}</div>
                </a>
            </li>
        @endcan

        @can('reports.view')
            <li class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bar-chart"></i>
                    <div class="text-truncate" data-i18n="Reports">{{ __('Reports') }}</div>
                </a>
            </li>
        @endcan
    </ul>
</aside>
