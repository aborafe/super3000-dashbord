<aside class="w-64 hidden md:flex flex-col bg-slate-900 text-slate-100 dark:bg-black">
    <div class="h-16 flex items-center px-4 border-b border-slate-800">
        <span class="font-semibold text-lg">
            {{ config('app.name', 'Super3000') }}
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-2">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                    <span class="flex-1">
                        {{ __('Dashboard') }}
                    </span>
                </a>
            </li>

            @can('products.view')
                <li>
                    <a href="{{ route('admin.products.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.products.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Products') }}
                        </span>
                    </a>
                </li>
            @endcan

            @can('orders.view')
                <li>
                    <a href="{{ route('admin.orders.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.orders.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Orders') }}
                        </span>
                    </a>
                </li>
            @endcan

            @can('partners.view')
                <li>
                    <a href="{{ route('admin.partners.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.partners.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Partners') }}
                        </span>
                    </a>
                </li>
            @endcan

            @can('warehouses.view')
                <li>
                    <a href="{{ route('admin.warehouses.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.warehouses.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Warehouses') }}
                        </span>
                    </a>
                </li>
            @endcan

            @can('employees.view')
                <li>
                    <a href="{{ route('admin.employees.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.employees.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Employees') }}
                        </span>
                    </a>
                </li>
            @endcan

            @can('reports.view')
                <li>
                    <a href="{{ route('admin.reports.index') }}"
                       class="flex items-center px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-slate-800' : 'hover:bg-slate-800/60' }}">
                        <span class="flex-1">
                            {{ __('Reports') }}
                        </span>
                    </a>
                </li>
            @endcan
        </ul>
    </nav>
</aside>

