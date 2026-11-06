@php($pageTitle = 'My profile')
@php($u = auth()->user())
@extends('layouts.store')
@section('title', 'My profile · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-account">@include('account._nav')
        <form method="post" action="{{ route('account.profile.save') }}" class="st-panel">
            @csrf @method('PUT')
            <h2>Your details</h2>
            <div class="pb-fgrid">
                <div class="pb-field"><label>Name</label><input name="name" required value="{{ old('name', $u->name) }}" autocomplete="name"></div>
                <div class="pb-field"><label>Email</label><input type="email" name="email" required value="{{ old('email', $u->email) }}" autocomplete="email"></div>
                <div class="pb-field"><label>Phone</label><input name="phone" value="{{ old('phone', $u->phone) }}" autocomplete="tel"></div>
                <div class="pb-field"><label>Country</label><input name="country" value="{{ old('country', $u->address['country'] ?? '') }}"></div>
                <div class="pb-field pb-full"><label>Address</label><input name="address" value="{{ old('address', $u->address['address'] ?? '') }}" autocomplete="street-address"></div>
                <div class="pb-field"><label>City</label><input name="city" value="{{ old('city', $u->address['city'] ?? '') }}"></div>
                <div class="pb-field"><label>Postcode</label><input name="postcode" value="{{ old('postcode', $u->address['postcode'] ?? '') }}"></div>
            </div>
            <h2 style="margin-top:30px">Change password</h2>
            <div class="pb-fgrid">
                <div class="pb-field pb-full"><label>Current password</label><input type="password" name="current_password" autocomplete="current-password"></div>
                <div class="pb-field"><label>New password</label><input type="password" name="password" autocomplete="new-password"></div>
                <div class="pb-field"><label>Repeat new password</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>
            </div>
            <button class="pb-btn pb-btn-primary" style="margin-top:24px" type="submit">Save changes</button>
        </form>
    </div>
@endsection
