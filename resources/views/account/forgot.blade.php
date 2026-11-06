@php($pageTitle = 'Forgot password')
@extends('layouts.store')
@section('title', 'Forgot password · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up"><form method="post" action="{{ route('account.forgot') }}" class="st-panel">
        @csrf
        <h2>Reset your password</h2>
        <p class="st-muted">Enter your email and we will send you a link to choose a new password.</p>
        <div class="pb-field"><label for="f-email">Email</label><input id="f-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}"></div>
        <button class="pb-btn pb-btn-primary st-wide" type="submit">Send reset link</button>
        <p class="st-muted st-center"><a href="{{ route('account.login') }}">Back to login</a></p>
    </form></div>
@endsection
