@php($pageTitle = 'Create an account')
@extends('layouts.store')
@section('title', 'Create an account · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up"><form method="post" action="{{ route('account.register') }}" class="st-panel">
        @csrf
        <h2>Create your account</h2>
        <div class="pb-hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div class="pb-field"><label for="r-name">Name</label><input id="r-name" name="name" required autocomplete="name" value="{{ old('name') }}"></div>
        <div class="pb-field"><label for="r-email">Email</label><input id="r-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}"></div>
        <div class="pb-field"><label for="r-pass">Password (8+ characters)</label><input id="r-pass" type="password" name="password" required autocomplete="new-password"></div>
        <div class="pb-field"><label for="r-pass2">Repeat password</label><input id="r-pass2" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="pb-btn pb-btn-primary st-wide" type="submit">Create account</button>
        <p class="st-muted st-center">Already registered? <a href="{{ route('account.login') }}">Log in</a></p>
    </form></div>
@endsection
