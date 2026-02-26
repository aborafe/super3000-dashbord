@php $locale = app()->getLocale(); @endphp
@php $themeClass = ($appearanceTheme ?? 'light') === 'dark' ? 'dark-style' : 'light-style'; @endphp
@php $dir = ($locale === 'ar' || !empty($appearanceRtl)) ? 'rtl' : 'ltr'; @endphp
<!doctype html>

<html
  lang="{{ $locale }}"
  dir="{{ $dir }}"
  class="layout-wide {{ $themeClass }}"
  data-assets-path="{{ asset('sneat-assets') }}/"
  data-template="vertical-menu-template-free">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title', __('Admin'))</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat-assets') }}/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('sneat-assets') }}/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css  -->

    <link rel="stylesheet" href="{{ asset('sneat-assets') }}/vendor/css/core.css" />
    <link rel="stylesheet" href="{{ asset('sneat-assets') }}/css/demo.css" />
    <link rel="stylesheet" href="{{ asset('loading-ui.css') }}" />
    <link rel="stylesheet" href="{{ asset('admin-overrides.css') }}" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="{{ asset('sneat-assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Helpers -->
    <script src="{{ asset('sneat-assets') }}/vendor/js/helpers.js"></script>

    <script src="{{ asset('sneat-assets') }}/js/config.js"></script>
  </head>

  <body>
    @yield('content')

    <!-- Core JS -->
    <script src="{{ asset('sneat-assets') }}/vendor/libs/jquery/jquery.js"></script>
    <script src="{{ asset('sneat-assets') }}/vendor/libs/popper/popper.js"></script>
    <script src="{{ asset('sneat-assets') }}/vendor/js/bootstrap.js"></script>
    <script src="{{ asset('sneat-assets') }}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script type="module" src="{{ asset('js/app.js') }}"></script>

    @yield('page-scripts')

    <script async defer src="https://buttons.github.io/buttons.js"></script>
  </body>
</html>

