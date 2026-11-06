@php($pageTitle = 'Choose a new password')
@extends('layouts.store')
@section('title', 'New password · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-auth" data-anim="fade-up"><form method="post" action="{{ route('account.reset.save') }}" class="st-panel">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h2>Choose a new password</h2>
        <div class="pb-field"><label for="n-email">Email</label><input id="n-email" type="email" name="email" required value="{{ old('email', $email) }}"></div>
        <div class="pb-field"><label for="n-pass">New password (8+ characters)</label><input id="n-pass" type="password" name="password" required autocomplete="new-password"></div>
        <div class="pb-field"><label for="n-pass2">Repeat new password</label><input id="n-pass2" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="pb-btn pb-btn-primary st-wide" type="submit">Save new password</button>
    </form></div>
@endsection
