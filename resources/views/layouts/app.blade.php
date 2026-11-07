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
    @include('partials.seo-head')
    <link rel="icon" href="{{ media(setting('site.favicon')) }}">

    <link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
    @if (! request()->routeIs('home'))
        <link rel="stylesheet" href="{{ asset('assets/css/pages.css') }}?v={{ filemtime(public_path('assets/css/pages.css')) }}">
        <link rel="stylesheet" href="{{ asset('assets/css/vibe.css') }}?v={{ filemtime(public_path('assets/css/vibe.css')) }}">
        <link rel="stylesheet" href="{{ asset('assets/css/tech-store.css') }}?v={{ filemtime(public_path('assets/css/tech-store.css')) }}">
        <link rel="stylesheet" href="{{ asset('assets/css/tech.css') }}?v={{ filemtime(public_path('assets/css/tech.css')) }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/tech.css') }}?v={{ filemtime(public_path('assets/css/tech.css')) }}">
        <link rel="stylesheet" href="{{ asset('assets/css/home-tech.css') }}?v={{ filemtime(public_path('assets/css/home-tech.css')) }}">
    @endif
    @stack('head')
    @stack('styles')
</head>
<body class="@yield('body_class')">

    @php($canvas = isset($page) && $page instanceof \App\Models\Page && $page->template === 'canvas')
    @unless ($canvas)
        @include('partials.header')
    @endunless

    <main>
        @yield('content')
    </main>

    @unless ($canvas)
        @include('partials.footer')
        @include('partials.floating')
    @endunless

    <script src="{{ asset('vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}?v={{ filemtime(public_path('assets/js/main.js')) }}"></script>
    @if (! request()->routeIs('home'))
        <script src="{{ asset('assets/js/motion.js') }}?v={{ filemtime(public_path('assets/js/motion.js')) }}"></script>
        <script src="{{ asset('assets/js/vibe.js') }}?v={{ filemtime(public_path('assets/js/vibe.js')) }}" defer></script>
        <script src="{{ asset('assets/js/tech.js') }}?v={{ filemtime(public_path('assets/js/tech.js')) }}" defer></script>
    @else
        <script src="{{ asset('assets/js/tech.js') }}?v={{ filemtime(public_path('assets/js/tech.js')) }}" defer></script>
        <script src="{{ asset('assets/js/home-tech.js') }}?v={{ filemtime(public_path('assets/js/home-tech.js')) }}" defer></script>
    @endif
    @stack('scripts')
    @if (! empty($editor))
        <script src="{{ asset('assets/admin/preview-bridge.js') }}?v={{ filemtime(public_path('assets/admin/preview-bridge.js')) }}"></script>
    @endif
</body>
</html>
