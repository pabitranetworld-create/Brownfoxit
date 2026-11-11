@php($pageTitle = 'My Account')
@extends('layouts.store')
@section('title', 'Log in · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up">
        <div class="st-panel st-auth-card">
            @include('account._auth-tabs', ['tab' => 'login'])
            <form method="post" action="{{ route('account.login') }}">
                @csrf
                <h2>Welcome back</h2>
                <p class="st-muted st-auth-sub">Log in with your email or mobile number.</p>
                <div class="pb-field"><label for="l-login">Email or mobile number</label><input id="l-login" name="login" required autofocus autocomplete="username" inputmode="email" value="{{ old('login') }}" placeholder="you@example.com or 01XXXXXXXXX"></div>
                <div class="pb-field"><label for="l-pass">Password</label><div class="st-pass"><input id="l-pass" type="password" name="password" required autocomplete="current-password"><button type="button" class="st-pass-eye" data-pass-eye aria-label="Show password"><i class="fa-regular fa-eye"></i></button></div></div>
                <div class="st-auth-row"><label class="pb-check"><input type="checkbox" name="remember" value="1" checked> <span>Keep me logged in</span></label><a href="{{ route('account.forgot') }}">Forgot password?</a></div>
                <button class="pb-btn pb-btn-primary st-wide" type="submit">Log in</button>
            </form>
            @include('account._auth-guest')
        </div>
    </div>
@endsection
