<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('site.meta_title', config('app.name')))</title>
    <meta name="description" content="@yield('meta_description', setting('site.meta_description'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ media(setting('site.favicon')) }}">

    <meta property="og:site_name" content="{{ setting('site.name', config('app.name')) }}">
    <meta property="og:title" content="@yield('title', setting('site.meta_title', config('app.name')))">
    <meta property="og:description" content="@yield('meta_description', setting('site.meta_description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    <link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">

    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.floating')

    <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}?v={{ filemtime(public_path('assets/js/main.js')) }}"></script>
    @stack('scripts')
</body>
</html>
