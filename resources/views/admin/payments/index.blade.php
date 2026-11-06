@extends('admin.layout')
@section('title', 'Payment gateways')
@section('content')
    <div class="adm-alert warn"><i class="fa-solid fa-circle-info"></i> <div>দোকানের কারেন্সি: <strong>{{ $currency }}</strong>। চেকআউটে শুধু সেই গেটওয়েগুলো দেখায় যেগুলো <strong>চালু</strong>, <strong>তথ্য পূরণ করা</strong> আর এই কারেন্সি নেয়। প্রতিটা অনলাইন পেমেন্ট গেটওয়ের সার্ভার থেকে যাচাই করার পরেই অর্ডার "Paid" হয়।</div></div>
    <div class="adm-gw-grid">
        @foreach ($gateways as $key => $g)
            @php($ok = $g->configured())
            @php($cur = $g->supports($currency))
            <div class="adm-card adm-gw">
                <div class="adm-gw-top">
                    <div><h3>{{ $g->title() }}</h3><small class="adm-muted">{{ $g::name() }} · {{ $g instanceof \App\Store\Gateways\Manual ? 'Manual' : ($g->sandbox() ? 'Test mode' : 'Live') }}</small></div>
                    <form method="POST" action="{{ route('admin.payments.toggle', $key) }}">@csrf
                        <button class="adm-toggle {{ $g->enabled() ? 'on' : '' }}" title="{{ $g->enabled() ? 'বন্ধ করুন' : 'চালু করুন' }}" aria-label="{{ $g->enabled() ? 'বন্ধ করুন' : 'চালু করুন' }}"><span></span></button>
                    </form>
                </div>
                <ul class="adm-gw-checks">
                    <li class="{{ $g->enabled() ? 'y' : 'n' }}">{{ $g->enabled() ? 'চালু' : 'বন্ধ' }}</li>
                    <li class="{{ $ok ? 'y' : 'n' }}">{{ $ok ? 'তথ্য পূরণ করা' : 'তথ্য বাকি' }}</li>
                    <li class="{{ $cur ? 'y' : 'n' }}">{{ $cur ? $currency.' নেয়' : $currency.' নেয় না' }}</li>
                </ul>
                <div class="adm-gw-actions">
                    <a class="adm-btn ghost sm" href="{{ route('admin.payments.edit', $key) }}"><i class="fa-solid fa-gear"></i> সেটিংস</a>
                    @if ($ok)<form method="POST" action="{{ route('admin.payments.test', $key) }}">@csrf<button class="adm-btn ghost sm"><i class="fa-solid fa-plug-circle-check"></i> সংযোগ টেস্ট</button></form>@endif
                </div>
            </div>
        @endforeach
    </div>
    <div class="adm-card" style="margin-top:20px">
        <h2 class="adm-h">নতুন গেটওয়ে যোগ করবেন কীভাবে</h2>
        <p class="adm-muted">১) বেশিরভাগ গেটওয়ে (SSLCommerz, shurjoPay, UddoktaPay ইত্যাদি) "পেমেন্ট তৈরি → পেমেন্ট পেজ → transaction status দিয়ে যাচাই" নিয়মে চলে — এগুলো <strong>Custom gateway</strong>-এ কোড ছাড়া বসানো যায়। ২) যে গেটওয়েতে আলাদা token/login ধাপ লাগে, তার জন্য app/Store/Gateways ফোল্ডারে একটা ছোট driver ফাইল লাগে (PayStation, aamarPay, EPS-এর ফাইলগুলো উদাহরণ)। আমাকে গেটওয়ের নাম আর ডকুমেন্টেশন দিলে বানিয়ে দেব।</p>
    </div>
@endsection
