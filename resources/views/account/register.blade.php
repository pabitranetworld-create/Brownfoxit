@php($pageTitle = 'My Account')
@extends('layouts.store')
@section('title', 'Create an account · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up">
        <div class="st-panel st-auth-card">
            @include('account._auth-tabs', ['tab' => 'register'])
            <form method="post" action="{{ route('account.register') }}">
                @csrf
                <h2>Create your account</h2>
                <p class="st-muted st-auth-sub">Takes a few seconds. You will be logged in right away.</p>
                <div class="pb-hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="pb-field"><label for="r-name">Full name</label><input id="r-name" name="name" required autocomplete="name" value="{{ old('name') }}"></div>
                <div class="pb-field"><label for="r-email">Email</label><input id="r-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}"></div>
                <div class="pb-field"><label for="r-phone">Mobile number</label><input id="r-phone" type="tel" name="phone" required autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="01XXXXXXXXX"></div>
                <div class="pb-field"><label for="r-pass">Password (8+ characters)</label><div class="st-pass"><input id="r-pass" type="password" name="password" required minlength="8" autocomplete="new-password"><button type="button" class="st-pass-eye" data-pass-eye aria-label="Show password"><i class="fa-regular fa-eye"></i></button></div></div>
                <button class="pb-btn pb-btn-primary st-wide" type="submit">Create account</button>
            </form>
            @include('account._auth-guest')
        </div>
    </div>
@endsection
