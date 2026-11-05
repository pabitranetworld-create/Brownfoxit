<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · Admin · {{ setting('site.name', config('app.name')) }}</title>
    <link rel="icon" href="{{ media(setting('site.favicon')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v={{ filemtime(public_path('assets/admin/admin.css')) }}">
</head>
<body>
<div class="adm">
    <aside class="adm-side" id="side">
        <a href="{{ route('admin.dashboard') }}" class="adm-brand">
            <img src="{{ media(setting('site.logo')) }}" alt="">
            <span>Admin</span>
        </a>

        <nav class="adm-nav">
            <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])><i class="fa-solid fa-gauge"></i> Dashboard</a>

            <a href="{{ route('admin.pages.index') }}" @class(['on' => request()->routeIs('admin.pages.*')])><i class="fa-solid fa-file-lines"></i> Pages (Page Builder)</a>
            <a href="{{ route('admin.media.index') }}" @class(['on' => request()->routeIs('admin.media.*')])><i class="fa-solid fa-photo-film"></i> Media library</a>

            <p class="adm-nav-h">Home Page</p>
            @foreach (\App\Admin\Schema::sections() as $sKey => $s)
                <a href="{{ route('admin.settings.edit', $sKey) }}" @class(['on' => request()->route('section') === $sKey])><i class="{{ $s['icon'] }}"></i> {{ $s['title'] }}</a>
            @endforeach

            <p class="adm-nav-h">কন্টেন্ট</p>
            @foreach (\App\Admin\Schema::resources() as $rKey => $r)
                <a href="{{ route('admin.resource.index', $rKey) }}" @class(['on' => request()->route('resource') === $rKey])><i class="{{ $r['icon'] }}"></i> {{ $r['title'] }}</a>
            @endforeach

            <p class="adm-nav-h">অ্যাকাউন্ট</p>
            <a href="{{ route('admin.profile') }}" @class(['on' => request()->routeIs('admin.profile')])><i class="fa-solid fa-user-gear"></i> প্রোফাইল ও পাসওয়ার্ড</a>
        </nav>
    </aside>

    <div class="adm-main">
        <header class="adm-top">
            <button class="adm-burger" id="burger" aria-label="মেনু"><i class="fa-solid fa-bars"></i></button>
            <h1>@yield('title', 'Dashboard')</h1>
            <div class="adm-top-r">
                <a href="{{ route('home') }}" target="_blank" class="adm-btn ghost sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>সাইট দেখুন</span></a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="adm-btn ghost sm" title="লগআউট"><i class="fa-solid fa-right-from-bracket"></i> <span>{{ auth()->user()->name }}</span></button>
                </form>
            </div>
        </header>

        <main class="adm-content">
            @if (session('status'))
                <div class="adm-alert ok"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="adm-alert err">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><strong>কিছু তথ্য ঠিক করতে হবে:</strong>
                        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<div class="adm-overlay" id="overlay"></div>
<script>window.BFI_MEDIA = { list: @json(route('admin.media.list')), upload: @json(route('admin.media.store')), base: @json(url('/')), csrf: @json(csrf_token()) };</script>
<script src="{{ asset('assets/admin/media-picker.js') }}?v={{ filemtime(public_path('assets/admin/media-picker.js')) }}"></script>
<script src="{{ asset('assets/admin/admin.js') }}?v={{ filemtime(public_path('assets/admin/admin.js')) }}"></script>
@stack('scripts')
</body>
</html>
