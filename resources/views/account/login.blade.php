@php($pageTitle = 'Log in')
@extends('layouts.store')
@section('title', 'Log in · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up"><form method="post" action="{{ route('account.login') }}" class="st-panel">
        @csrf
        <h2>Welcome back</h2>
        <div class="pb-field"><label for="l-email">Email</label><input id="l-email" type="email" name="email" required autofocus autocomplete="email" value="{{ old('email') }}"></div>
        <div class="pb-field"><label for="l-pass">Password</label><input id="l-pass" type="password" name="password" required autocomplete="current-password"></div>
        <div class="st-auth-row"><label class="pb-check"><input type="checkbox" name="remember" value="1"> <span>Remember me</span></label><a href="{{ route('account.forgot') }}">Forgot password?</a></div>
        <button class="pb-btn pb-btn-primary st-wide" type="submit">Log in</button>
        @if (store('registration', true))<p class="st-muted st-center">New here? <a href="{{ route('account.register') }}">Create an account</a></p>@endif
    </form></div>
@endsection
