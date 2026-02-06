@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $isRtl = $dir === 'rtl';
    $title = trim(($title ?? '') . ' – ' . config('app.name', 'Super3000'), ' –');
    $theme = session('theme', 'light');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('theme');
                if (storedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                if (storedTheme === 'light') {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                // ignore
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-900 dark:text-slate-50">
    <div class="min-h-screen flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
        @include('partials.sidebar')

        <div class="flex-1 flex flex-col">
            @include('partials.navbar')

            <main class="flex-1 p-4 md:p-6">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

