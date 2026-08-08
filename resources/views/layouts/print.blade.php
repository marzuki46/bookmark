<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cetak')</title>
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body>
    <main class="print-body">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
