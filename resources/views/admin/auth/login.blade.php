<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login · {{ setting('site.name', config('app.name')) }}</title>
    <link rel="icon" href="{{ media(setting('site.favicon')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/admin.css') }}?v={{ filemtime(public_path('assets/admin/admin.css')) }}">
</head>
<body class="adm-login-page">
    <form class="adm-login" method="POST" action="{{ route('admin.login.attempt') }}">
        @csrf
        <img class="adm-login-logo" src="{{ media(setting('site.logo')) }}" alt="{{ setting('site.name') }}">
        <h1>Admin লগইন</h1>
        <p class="adm-muted">ওয়েবসাইট পরিচালনা করতে লগইন করুন</p>

        @if (session('status'))
            <div class="adm-alert ok">{{ session('status') }}</div>
        @endif
        @error('email')
            <div class="adm-alert err">{{ $message }}</div>
        @enderror
        @error('password')
            <div class="adm-alert err">{{ $message }}</div>
        @enderror

        <label for="email">ইমেইল</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">পাসওয়ার্ড</label>
        <div class="adm-pass">
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button type="button" data-toggle-pass="password" aria-label="পাসওয়ার্ড দেখুন"><i class="fa-regular fa-eye"></i></button>
        </div>

        <label class="adm-check"><input type="checkbox" name="remember" value="1"> মনে রাখুন</label>

        <button class="adm-btn full" type="submit">লগইন <i class="fa-solid fa-arrow-right"></i></button>
        <a class="adm-back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> ওয়েবসাইটে ফিরে যান</a>
    </form>
    <script src="{{ asset('assets/admin/admin.js') }}?v={{ filemtime(public_path('assets/admin/admin.js')) }}"></script>
</body>
</html>
