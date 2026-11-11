@php($pageTitle = 'Cart')
@extends('layouts.store')
@section('title', 'Cart · '.setting('site.name'))
@section('robots', 'noindex, nofollow')

@section('store')
    @if (! $t['lines'])
        <div class="st-empty" data-anim="fade-up"><i class="fa-solid fa-cart-shopping"></i><h2>Your cart is empty</h2><p>Browse the shop and add something you like.</p><a class="st-btn" href="{{ route('shop.index') }}">Go to the shop</a></div>
    @else
        <div class="st-cart">
            <form method="post" action="{{ route('cart.update') }}" class="st-cart-items" data-anim="fade-up">
                @csrf
                @foreach ($t['lines'] as $l)
                    <div class="st-line">
                        <a href="{{ $l['product']->link() }}" class="st-line-img">@if ($l['product']->image)<img src="{{ media($l['product']->image) }}" alt="">@else<span class="st-noimg"><i class="fa-solid fa-box-open"></i></span>@endif</a>
                        <div class="st-line-info">
                            <a href="{{ $l['product']->link() }}"><strong>{{ $l['product']->name }}</strong></a>
                            @if ($l['variant_name'])<small>{{ $l['variant_name'] }}</small>@endif
                            <span class="st-line-price">{{ money($l['price']) }} @if ($l['price'] < $l['regular'])<del>{{ money($l['regular']) }}</del>@endif</span>
                        </div>
                        @if ($l['product']->isDigital())
                            <span class="st-line-qty">× 1</span>
                        @else
                            <label class="st-qty sm"><span class="sr-only">Quantity</span><button type="button" data-qty="-1">−</button><input type="number" name="qty[{{ $l['id'] }}]" value="{{ $l['qty'] }}" min="0" max="99"><button type="button" data-qty="1">+</button></label>
                        @endif
                        <strong class="st-line-total">{{ money($l['total']) }}</strong>
                        <button type="submit" form="rm-{{ $l['id'] }}" class="st-rm" aria-label="Remove {{ $l['product']->name }}"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endforeach
                <div class="st-cart-actions"><a href="{{ route('shop.index') }}" class="pb-btn pb-btn-link"><i class="fa-solid fa-arrow-left"></i> <span>Continue shopping</span></a><button class="st-btn ghost" type="submit">Update cart</button></div>
            </form>
            @foreach ($t['lines'] as $l)<form id="rm-{{ $l['id'] }}" method="post" action="{{ route('cart.remove', $l['id']) }}">@csrf</form>@endforeach

            <aside class="st-side" data-anim="fade-left">
                <h2>Order summary</h2>
                @include('shop.partials.totals')
                <a class="pb-btn pb-btn-primary st-wide" href="{{ route('checkout.show') }}">Proceed to checkout <i class="fa-solid fa-arrow-right"></i></a>
                @if (store('coupons_enabled', true) && ! $coupon)<p class="st-muted st-coupon-hint"><i class="fa-solid fa-ticket"></i> Have a coupon? You can add it at checkout.</p>@endif
            </aside>
        </div>
    @endif
@endsection
