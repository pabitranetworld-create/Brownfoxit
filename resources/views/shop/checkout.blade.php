@php($pageTitle = 'Checkout')
@extends('layouts.store')
@section('title', 'Checkout · '.setting('site.name'))
@section('robots', 'noindex, nofollow')

@section('store')
    <ol class="st-progress" aria-label="Checkout steps"><li class="done"><span>1</span> Cart</li><li class="on"><span>2</span> Details & payment</li><li><span>3</span> Confirmation</li></ol>
    <form method="post" action="{{ route('checkout.place') }}" class="st-checkout" data-checkout novalidate>
        @csrf
        <div class="st-co-main">
            <section class="st-panel" data-anim="fade-up">
                <h2><span class="n">1</span> Your details</h2>
                @guest<p class="st-muted">Already have an account? <a href="{{ route('account.login') }}">Log in</a> to see your orders later.</p>@endguest
                <div class="pb-fgrid">
                    @php($u = $user)
                    <div class="pb-field"><label for="co-name">Full name <span>*</span></label><input id="co-name" name="name" required autocomplete="name" value="{{ old('name', $u?->name) }}"></div>
                    <div class="pb-field"><label for="co-email">Email <span>*</span></label><input id="co-email" type="email" name="email" required autocomplete="email" value="{{ old('email', $u?->email) }}"><small class="st-muted">Your receipt{{ $t['needs_shipping'] ? '' : ' and download links' }} will be sent here.</small></div>
                    <div class="pb-field"><label for="co-phone">Phone @if (store('require_phone', true))<span>*</span>@endif</label><input id="co-phone" name="phone" autocomplete="tel" value="{{ old('phone', $u?->phone) }}"></div>
                    <div class="pb-field"><label for="co-company">Company (optional)</label><input id="co-company" name="company" autocomplete="organization" value="{{ old('company') }}"></div>
                    @if ($t['needs_shipping'])
                        <div class="pb-field pb-full"><label for="co-address">Shipping address <span>*</span></label><input id="co-address" name="address" required autocomplete="street-address" value="{{ old('address', $u?->address['address'] ?? '') }}"></div>
                        <div class="pb-field"><label for="co-city">City <span>*</span></label><input id="co-city" name="city" required autocomplete="address-level2" value="{{ old('city', $u?->address['city'] ?? '') }}"></div>
                        <div class="pb-field"><label for="co-post">Postcode</label><input id="co-post" name="postcode" autocomplete="postal-code" value="{{ old('postcode', $u?->address['postcode'] ?? '') }}"></div>
                    @endif
                    <div class="pb-field"><label for="co-country">Country</label><input id="co-country" name="country" autocomplete="country-name" value="{{ old('country', $u?->address['country'] ?? (store('currency', 'BDT') === 'BDT' ? 'Bangladesh' : '')) }}"></div>
                    <div class="pb-field pb-full"><label for="co-note">Order note (optional)</label><textarea id="co-note" name="note" rows="3">{{ old('note') }}</textarea></div>
                    @if (store('coupons_enabled', true))
                        <div class="pb-field pb-full st-co-coupon" data-coupon>
                            @if ($t['coupon'])
                                <p class="st-coupon-on"><i class="fa-solid fa-ticket"></i> Coupon <strong>{{ $t['coupon']->code }}</strong> applied <button type="submit" class="st-link-btn" formaction="{{ route('cart.coupon') }}" formnovalidate name="remove" value="1" data-coupon-remove>Remove</button></p>
                            @else
                                <div class="pb-check st-coupon-toggle"><label><input type="checkbox" data-toggle="#co-coupon-box" @checked($errors->has('coupon'))> <span>Do you have a coupon code?</span></label></div>
                                <div class="st-coupon-box" id="co-coupon-box" @unless ($errors->has('coupon')) hidden @endunless>
                                    <input name="code" placeholder="Enter coupon code" aria-label="Coupon code" maxlength="50" autocomplete="off" value="{{ old('code') }}">
                                    <button type="submit" class="st-btn ghost sm" formaction="{{ route('cart.coupon') }}" formnovalidate data-coupon-apply>Apply</button>
                                </div>
                            @endif
                            <p class="st-coupon-msg" role="status" @if ($errors->has('coupon')) data-err @endif>{{ $errors->first('coupon') }}</p>
                        </div>
                    @endif
                    @guest
                        @if (store('registration', true))
                            <div class="pb-field pb-full pb-check"><label><input type="checkbox" name="create_account" value="1" @checked(old('create_account')) data-toggle="#co-pass-wrap"> <span>Create an account for faster checkout and easy access to downloads</span></label></div>
                            <div class="pb-field pb-full" id="co-pass-wrap" @unless (old('create_account')) hidden @endunless><label for="co-pass">Choose a password (8+ characters)</label><input id="co-pass" type="password" name="password" autocomplete="new-password"></div>
                        @endif
                    @endguest
                </div>
            </section>

            <section class="st-panel" data-anim="fade-up">
                <h2><span class="n">2</span> Payment method</h2>
                @if (! $gateways)
                    <div class="st-alert warn"><i class="fa-solid fa-circle-info"></i> Online payment is not available yet. Please <a href="{{ url('/contact') }}">contact us</a> to complete your order.</div>
                @else
                    <div class="st-gateways" role="radiogroup">
                        @foreach ($gateways as $key => $g)
                            <label class="st-gateway">
                                <input type="radio" name="gateway" value="{{ $key }}" @checked(old('gateway', array_key_first($gateways)) === $key) required>
                                <span class="st-gw-head"><strong>{{ $g->title() }}</strong>@if ($g->sandbox() && ! $g instanceof \App\Store\Gateways\Manual)<em>Test mode</em>@endif</span>
                                @if ($g->description())<span class="st-gw-desc">{{ $g->description() }}</span>@endif
                                @if ($g instanceof \App\Store\Gateways\Manual)
                                    <span class="st-gw-more">
                                        <span class="st-instr">{!! nl2br(e($g->config['instructions'] ?? '')) !!}</span>
                                        @if ($g->asksReference())<span class="pb-field"><label for="ref-{{ $key }}">{{ $g->config['reference_label'] ?? 'Transaction ID' }} <span>*</span></label><input id="ref-{{ $key }}" name="reference" value="{{ old('reference') }}" maxlength="120" data-ref></span>@endif
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="st-side" data-anim="fade-left">
            <h2>Your order</h2>
            <ul class="st-mini">
                @foreach ($t['lines'] as $l)<li><span>{{ $l['product']->name }}@if ($l['variant_name']) <small>({{ $l['variant_name'] }})</small>@endif × {{ $l['qty'] }}</span><strong>{{ money($l['total']) }}</strong></li>@endforeach
            </ul>
            <div data-totals>@include('shop.partials.totals')</div>
            @if (filled(store('terms_url')))
                <label class="pb-check st-terms"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required> <span>I have read and agree to the <a href="{{ link_to_url(store('terms_url')) }}" target="_blank">terms and conditions</a>.</span></label>
            @endif
            <button type="submit" class="pb-btn pb-btn-primary st-wide st-place" @disabled(! $gateways)><span class="t">Place order — {{ money($t['total']) }}</span><span class="pb-spin" aria-hidden="true"></span></button>
            <p class="st-muted st-secure"><i class="fa-solid fa-lock"></i> Payments are processed by the payment provider. We never see or store your card details.</p>
        </aside>
    </form>
@endsection
