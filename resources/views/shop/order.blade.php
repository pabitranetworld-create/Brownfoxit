@php
    $pageTitle = 'Order '.$order->number;
    $done = $order->isPaid();
@endphp
@extends('layouts.store')
@section('title', 'Order '.$order->number.' · '.setting('site.name'))
@section('robots', 'noindex, nofollow')

@section('store')
    <ol class="st-progress" aria-label="Checkout steps"><li class="done"><span>1</span> Cart</li><li class="done"><span>2</span> Details & payment</li><li @class(['on' => ! $done, 'done' => $done])><span>3</span> Confirmation</li></ol>

    <div class="st-order-head" data-anim="zoom-in">
        @if ($done)
            <div class="pb-done-ic"><svg viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M15 27l7 7 15-16"/></svg></div>
            <h2>Thank you — your payment is confirmed</h2>
            <p>Order <strong>{{ $order->number }}</strong> · {{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</p>
        @elseif (in_array($order->status, ['cancelled', 'refunded'], true))
            <h2>Order {{ \App\Models\Order::STATUSES[$order->status] }}</h2>
        @elseif ($order->payment_status === 'pending')
            <div class="st-wait" aria-hidden="true"><span></span><span></span><span></span></div>
            <h2>Waiting for payment confirmation</h2>
            <p>{{ $gw instanceof \App\Store\Gateways\Manual ? 'We will check your payment and confirm your order. You will receive an email when it is confirmed.' : 'The payment provider has not confirmed the payment yet. Refresh this page in a moment.' }}</p>
        @else
            <h2>Your order is saved — payment is still needed</h2>
            <p>Order <strong>{{ $order->number }}</strong>. Choose a payment method below to complete it.</p>
        @endif
    </div>

    <div class="st-cart">
        <div>
            @if ($gw instanceof \App\Store\Gateways\Manual && ! $done && ! in_array($order->status, ['cancelled', 'refunded'], true))
                <section class="st-panel"><h2>How to pay</h2><p class="st-instr">{!! nl2br(e($gw->config['instructions'] ?? '')) !!}</p><p class="st-muted">Reference: <strong>{{ $order->number }}</strong>@if ($order->manual_reference) · your transaction ID: <strong>{{ $order->manual_reference }}</strong>@endif</p></section>
            @endif

            @if ($done && $order->downloads->isNotEmpty())
                <section class="st-panel" data-anim="fade-up"><h2><i class="fa-solid fa-download"></i> Your downloads</h2>
                    <ul class="st-dl">
                        @foreach ($order->downloads as $d)
                            <li>
                                <span><strong>{{ $d->file_name }}</strong><small>@if ($d->downloads_remaining !== null){{ $d->downloads_remaining }} downloads left · @endif @if ($d->expires_at)available until {{ $d->expires_at->format('d M Y') }}@else no expiry @endif</small></span>
                                @if ($p = $d->problem())<em>{{ $p }}</em>@else<a class="st-btn sm" href="{{ $d->link() }}"><i class="fa-solid fa-download"></i> Download</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($gateways)
                <section class="st-panel" id="pay"><h2>{{ $order->payments->isEmpty() ? 'Pay for this order' : 'Try the payment again' }}</h2>
                    <form method="post" action="{{ route('shop.order.pay', $order->number) }}" class="st-gateways">
                        @csrf<input type="hidden" name="key" value="{{ $order->access_key }}">
                        @foreach ($gateways as $key => $g)
                            <label class="st-gateway"><input type="radio" name="gateway" value="{{ $key }}" @checked($loop->first) required><span class="st-gw-head"><strong>{{ $g->title() }}</strong></span>@if ($g->description())<span class="st-gw-desc">{{ $g->description() }}</span>@endif
                                @if ($g instanceof \App\Store\Gateways\Manual)<span class="st-gw-more"><span class="st-instr">{!! nl2br(e($g->config['instructions'] ?? '')) !!}</span>@if ($g->asksReference())<span class="pb-field"><label>{{ $g->config['reference_label'] ?? 'Transaction ID' }}</label><input name="reference" maxlength="120" data-ref></span>@endif</span>@endif
                            </label>
                        @endforeach
                        <button class="pb-btn pb-btn-primary st-place" type="submit"><span class="t">Pay {{ money($order->total, $order->currency) }}</span><span class="pb-spin" aria-hidden="true"></span></button>
                    </form>
                </section>
            @endif

            <section class="st-panel"><h2>Order details</h2>
                <ul class="st-mini">@foreach ($order->items as $i)<li><span>{{ $i->name }}@if ($i->variant_name) <small>({{ $i->variant_name }})</small>@endif × {{ $i->qty }}</span><strong>{{ money($i->total, $order->currency) }}</strong></li>@endforeach</ul>
                @include('shop.partials.totals', ['t' => ['subtotal' => $order->subtotal, 'discount' => (float) $order->discount, 'coupon' => $order->coupon_code ? (object) ['code' => $order->coupon_code] : null, 'needs_shipping' => $order->needs_shipping, 'shipping' => (float) $order->shipping, 'tax' => (float) $order->tax, 'total' => $order->total]])
            </section>
        </div>
        <aside class="st-side">
            <h2>Summary</h2>
            <dl class="st-totals">
                <div><dt>Order</dt><dd>{{ $order->number }}</dd></div>
                <div><dt>Date</dt><dd>{{ $order->created_at->format('d M Y') }}</dd></div>
                <div><dt>Status</dt><dd>{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</dd></div>
                <div><dt>Payment</dt><dd>{{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</dd></div>
                <div><dt>Method</dt><dd>{{ \App\Store\Gateways::label($order->payment_method) }}</dd></div>
                <div class="total"><dt>Total</dt><dd>{{ money($order->total, $order->currency) }}</dd></div>
            </dl>
            <p class="st-muted">Billing: {{ $order->billing['name'] ?? '' }}, {{ $order->billing['email'] ?? '' }}</p>
            @auth<a class="pb-btn pb-btn-link" href="{{ route('account.orders') }}"><span>All my orders</span> <i class="fa-solid fa-arrow-right"></i></a>@endauth
            <p class="st-muted">Questions about this order? <a href="{{ url('/contact') }}">Contact us</a> and mention {{ $order->number }}.</p>
        </aside>
    </div>
@endsection
