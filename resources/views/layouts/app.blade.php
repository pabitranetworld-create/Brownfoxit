<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (! empty($editor)) data-editor @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @unless (request()->routeIs('home'))
        {{-- Hide animated elements only when JS runs; show everything if motion.js never loads. --}}
        <script>(function(d){var r=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;d.className+=' js-motion'+(r?' reduced':'');setTimeout(function(){if(!window.__motionReady){d.className=d.className.replace(/\bjs-motion\b/,'')}},2500)})(document.documentElement)</script>
    @endunless
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
    @if (! request()->routeIs('home'))
        <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}?v={{ filemtime(public_path('assets/css/pages.css')) }}">
    @endif
    @stack('head')
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
    @if (! request()->routeIs('home'))
        <script src="{{ asset('assets/js/motion.js') }}?v={{ filemtime(public_path('assets/js/motion.js')) }}"></script>
    @endif
    @stack('scripts')
    @if (! empty($editor))
        <script src="{{ asset('assets/admin/preview-bridge.js') }}?v={{ filemtime(public_path('assets/admin/preview-bridge.js')) }}"></script>
    @endif
</body>
</html>
