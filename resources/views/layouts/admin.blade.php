@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
    $assetsPath = asset('admin/assets') . '/';
    $coreCss = $isRtl && file_exists(public_path('admin/assets/vendor/css/rtl/core.css'))
        ? 'admin/assets/vendor/css/rtl/core.css'
        : 'admin/assets/vendor/css/core.css';
    $themeCss = $isRtl && file_exists(public_path('admin/assets/vendor/css/rtl/theme-default.css'))
        ? 'admin/assets/vendor/css/rtl/theme-default.css'
        : 'admin/assets/vendor/css/theme-default.css';
@endphp

<!doctype html>
<html
    lang="{{ str_replace('_', '-', $locale) }}"
    dir="{{ $dir }}"
    class="layout-menu-fixed layout-compact"
    data-assets-path="{{ $assetsPath }}"
    data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="description" content="" />

    <title>
        @hasSection('title')
            @yield('title') | {{ config('app.name', 'Super3000') }}
        @else
            {{ config('app.name', 'Super3000') }}
        @endif
    </title>

    <link rel="icon" type="image/x-icon" href="{{ asset('admin/assets/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />

    <script>
        (function () {
            var storageKey = 'templateCustomizer';
            var defaultTheme = 'light';
            var root = document.documentElement;
            var theme = defaultTheme;

            try {
                var stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
                if (stored && (stored.theme === 'dark' || stored.theme === 'light')) {
                    theme = stored.theme;
                } else {
                    var legacy = localStorage.getItem('theme');
                    if (legacy === 'dark' || legacy === 'light') {
                        theme = legacy;
                        localStorage.setItem(storageKey, JSON.stringify({ theme: legacy }));
                    }
                }
            } catch (e) {
                // ignore
            }

            root.setAttribute('data-bs-theme', theme);
            root.classList.remove('light-style', 'dark-style');
            root.classList.add(theme === 'dark' ? 'dark-style' : 'light-style');
        })();
    </script>

    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset($coreCss) }}" />
    <link rel="stylesheet" href="{{ asset($themeCss) }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    @stack('styles')

    <script src="{{ asset('admin/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('admin/assets/js/config.js') }}"></script>

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar vite-admin-proof">
        <div class="layout-container">
            @include('admin.partials.sidebar')

            <!-- Layout container -->
            <div class="layout-page">
                @include('admin.partials.navbar')

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>

                    @include('admin.partials.footer')

                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>

    @stack('vendor-scripts')

    <script src="{{ asset('admin/assets/js/main.js') }}"></script>

    @stack('scripts')

</body>
</html>
