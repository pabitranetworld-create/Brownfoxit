@php
    $images = array_values(array_filter(array_merge([$product->image], array_map(fn ($g) => is_array($g) ? ($g['image'] ?? null) : $g, $product->gallery ?? []))));
    $variants = $product->variants ?? [];
    $first = $variants[0]['key'] ?? null;
    [$regular, $sale] = $product->prices($first);
    $save = $sale !== null && $regular > 0 ? (int) round(($regular - $sale) / $regular * 100) : 0;
    $features = array_values(array_filter(array_map(fn ($f) => trim(is_array($f) ? ($f['text'] ?? '') : (string) $f), $product->features ?? [])));
    $digital = $product->isDigital();
    $inStock = $product->inStock($first) || $variants;
    $words = preg_split('/\s+/', trim($product->name));
    $lastWord = count($words) > 1 ? array_pop($words) : null;
    $tabs = array_filter([
        'description' => filled($product->description) ? 'Overview' : null,
        'specs' => ! empty($product->specifications) ? 'Specifications' : null,
        'requirements' => filled($product->requirements) || filled($product->compatibility) ? 'Requirements' : null,
        'changelog' => filled($product->changelog) ? 'Changelog' : null,
    ]);
    $icons = ['fa-solid fa-bolt', 'fa-solid fa-wand-magic-sparkles', 'fa-solid fa-shield-halved', 'fa-solid fa-rocket', 'fa-solid fa-gem', 'fa-solid fa-layer-group', 'fa-solid fa-chart-line', 'fa-solid fa-puzzle-piece'];
    $colors = ['orange', 'blue', 'green', 'purple'];
    $steps = $digital
        ? [['fa-solid fa-cart-shopping', 'Choose & check out', 'Pick the option you need and pay with a payment method that suits you.'], ['fa-solid fa-circle-check', 'Payment confirmed', 'Online payments are confirmed automatically; manual payments as soon as we have checked them.'], ['fa-solid fa-download', 'Download', 'Your download links appear on the order page, in your account and in the confirmation email.']]
        : [['fa-solid fa-cart-shopping', 'Place your order', 'Choose the product and quantity, then check out with your delivery address.'], ['fa-solid fa-circle-check', 'We confirm', 'We confirm your payment and order and keep you updated by email.'], ['fa-solid fa-truck-fast', 'Delivery', 'Your order is packed and sent to the address you gave us.']];
@endphp
@extends('layouts.app')

@php(seo()->subject($product))
@section('title', $product->name)
@section('body_class', 'page-inner tpl-full_width theme-dark top-dark vp-page')

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="pb-section sc-dark bg-tech vp-hero" id="buy">
        <div class="tb-glow" aria-hidden="true"><span></span><span></span><span></span></div>
        @include('pages.builder.fx-tech', ['fx' => 'circuit'])
        <div class="pb-container">
            @if ($product->status !== 'published')<div class="st-alert warn"><i class="fa-solid fa-eye-slash"></i> Preview — this product is <strong>{{ $product->status }}</strong> and not visible to customers.</div>@endif
            @if (session('status'))<div class="st-alert ok" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>@endif
            @if ($errors->any())<div class="st-alert err" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <div>@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div></div>@endif

            <div class="vp-hero-grid">
                <div class="vp-hero-text">
                    <nav class="vp-crumbs" aria-label="Breadcrumb" data-anim="fade-up">
                        <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i><a href="{{ route('shop.index') }}">Shop</a>
                        @if ($product->category)@foreach ($product->category->ancestors() as $a)<i class="fa-solid fa-angle-right"></i><a href="{{ $a->link() }}">{{ $a->name }}</a>@endforeach<i class="fa-solid fa-angle-right"></i><a href="{{ $product->category->link() }}">{{ $product->category->name }}</a>@endif
                    </nav>
                    <p class="vb-eyebrow" data-anim="fade-up">{{ $product->category?->name ?: ($digital ? 'Digital product' : 'Product') }}</p>
                    <h1 class="vp-title" data-words>{{ implode(' ', $words) }}@if ($lastWord) <span class="vb-grad">{{ $lastWord }}</span>@endif</h1>
                    @if ($product->short_description)<div class="pb-text vp-short" data-anim="fade-up" data-anim-delay="200">{!! \App\PageBuilder\HtmlSanitizer::clean($product->short_description) !!}</div>@endif

                    <form method="post" action="{{ route('cart.add') }}" class="vp-buybox st-buy" data-add-cart data-full data-anim="fade-up" data-anim-delay="300">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div class="vp-price-row">
                            <div class="st-price-big" data-price>@include('shop.partials.price', ['product' => $product, 'variant' => $first])</div>
                            @if ($save > 0)<span class="vp-save"><i class="fa-solid fa-tag"></i> Save {{ $save }}%</span>@endif
                        </div>
                        @if ($variants)
                            <fieldset class="st-variants vp-variants"><legend>Choose an option</legend>
                                @foreach ($variants as $i => $v)
                                    @php([$r, $s] = $product->prices($v['key']))
                                    <label @class(['st-variant', 'off' => ! $product->inStock($v['key'])])>
                                        <input type="radio" name="variant" value="{{ $v['key'] }}" @checked($i === 0) @disabled(! $product->inStock($v['key'])) data-price-html="{{ $s !== null ? '<del>'.e(money($r)).'</del> <ins>'.e(money($s)).'</ins>' : e(money($r)) }}">
                                        <span>{{ $v['name'] }}</span><small>{{ money($s ?? $r) }}</small>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif
                        <div class="st-buy-row">
                            @unless ($digital)
                                <label class="st-qty"><span class="sr-only">Quantity</span><button type="button" data-qty="-1" aria-label="Less">−</button><input type="number" name="qty" value="1" min="1" max="99" inputmode="numeric"><button type="button" data-qty="1" aria-label="More">+</button></label>
                            @endunless
                            @if ($inStock)
                                <button type="submit" class="pb-btn pb-btn-primary st-add"><i class="fa-solid fa-cart-plus"></i> <span>Add to cart</span></button>
                                <button type="submit" name="buy_now" value="1" class="pb-btn pb-btn-ghost st-buynow"><i class="fa-solid fa-bolt"></i> Buy now</button>
                            @else
                                <span class="st-out big">Out of stock</span>
                            @endif
                        </div>
                        <p class="st-buy-msg" role="status" aria-live="polite"></p>
                    </form>

                    <ul class="vp-trust" data-stagger="80">
                        <li class="pb-item"><i class="fa-solid {{ $digital ? 'fa-download' : 'fa-truck-fast' }}"></i> {{ $digital ? 'Instant download after payment' : 'Delivered to your address' }}</li>
                        <li class="pb-item"><i class="fa-solid fa-lock"></i> Secure checkout</li>
                        @if (! is_null($product->stockLeft($first)))<li class="pb-item"><i class="fa-solid fa-boxes-stacked"></i> {{ $product->stockLeft($first) }} in stock</li>@endif
                        @if ($product->version)<li class="pb-item"><i class="fa-solid fa-code-branch"></i> Version {{ $product->version }}</li>@endif
                    </ul>
                    @if ($product->demo_url || $product->documentation_url)
                        <div class="pb-btns vp-links">
                            @if ($product->demo_url)<a class="pb-btn pb-btn-link" href="{{ $product->demo_url }}" target="_blank" rel="noopener"><span>Live demo</span> <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
                            @if ($product->documentation_url)<a class="pb-btn pb-btn-link" href="{{ $product->documentation_url }}" target="_blank" rel="noopener"><span>Documentation</span> <i class="fa-solid fa-book"></i></a>@endif
                        </div>
                    @endif
                </div>

                <div class="vp-visual" data-gallery data-anim="zoom-in" data-anim-delay="150">
                    <div class="vp-ring" aria-hidden="true"></div>
                    @if ($images)<div class="vp-orbit" aria-hidden="true"><i></i><i></i><i></i></div>@endif
                    @if ($images)
                        <div class="vp-frame vb-tilt"><img src="{{ media($images[0]) }}" alt="{{ $product->name }}" data-main></div>
                    @else
                        <div class="tv-wrap vp-scene" aria-hidden="true">@include('pages.visuals.scene', ['scene' => $digital ? 'chip' : 'shop'])</div>
                    @endif
                    <span class="vp-badge b1"><i class="fa-solid {{ $digital ? 'fa-bolt' : 'fa-truck-fast' }}"></i> {{ $digital ? 'Instant access' : 'Fast delivery' }}</span>
                    @if ($save > 0)<span class="vp-badge b2"><i class="fa-solid fa-fire"></i> {{ $save }}% off</span>@else<span class="vp-badge b2"><i class="fa-solid fa-shield-halved"></i> Secure payment</span>@endif
                    @if (count($images) > 1)
                        <div class="st-thumbs vp-thumbs">
                            @foreach ($images as $i => $img)<button type="button" @class(['on' => $i === 0]) data-src="{{ media($img) }}" aria-label="Image {{ $i + 1 }}"><img src="{{ media($img) }}" alt="" loading="lazy"></button>@endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FEATURES ============ --}}
    @if ($features)
        <section class="pb-section sc-dark bg-tech2 vp-section" style="--pt:90px;--pb:90px">
            <div class="pb-fx vb-beams" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <div class="pb-container">
                <div class="vp-head ta-center" data-anim="fade-up"><div class="pb-heading"><p class="pb-eyebrow">Features</p><h2 class="pb-h size-xl">What you get</h2></div></div>
                <div class="pb-grid cols-{{ count($features) >= 4 && count($features) % 3 !== 0 ? 4 : 3 }} pb-iconboxes st-pop" data-anim="fade-up" data-stagger="70">
                    @foreach ($features as $i => $f)
                        <div class="pb-item pb-ibox">
                            <span class="pb-ibox-ic c-{{ $colors[$i % 4] }}"><i class="{{ $icons[$i % count($icons)] }}"></i></span>
                            <h3>{{ $f }}</h3>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ VIDEO ============ --}}
    @if ($product->video_url)
        <section class="pb-section sc-dark vp-section" style="--pt:90px;--pb:40px">
            <div class="pb-fx vb-aurora" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
            <div class="pb-container">
                <div class="vp-head ta-center" data-anim="fade-up"><div class="pb-heading"><p class="pb-eyebrow">See it in action</p><h2 class="pb-h size-xl">Watch the video</h2></div></div>
                <div class="vp-video" data-anim="zoom-in">@include('pages.widgets.video', ['d' => ['url' => $product->video_url, 'poster' => $product->image ?? '', 'title' => $product->name, 'mode' => 'popup', 'ratio' => '16/9'], 'wid' => 'pv'])</div>
            </div>
        </section>
    @endif

    {{-- ============ DETAILS + STICKY BUY CARD ============ --}}
    @if ($tabs)
        <section class="pb-section sc-dark vp-section" style="--pt:90px;--pb:90px">
            <div class="pb-fx vb-aurora" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
            <div class="pb-container vp-details">
                <div class="pb-tabs" data-tabs data-anim="fade-up">
                    <div class="pb-tab-list" role="tablist">
                        @foreach ($tabs as $k => $label)<button type="button" role="tab" @class(['pb-tab', 'on' => $loop->first]) data-tab="{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>@endforeach
                        <span class="pb-tab-ink"></span>
                    </div>
                    @foreach ($tabs as $k => $label)
                        <div class="pb-tab-panel" role="tabpanel" data-panel="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                            @switch($k)
                                @case('description')<div class="pb-text vp-desc">{!! \App\PageBuilder\HtmlSanitizer::clean((string) $product->description) !!}</div>@break
                                @case('specs')<table class="st-specs">@foreach ($product->specifications as $sp)<tr><th>{{ $sp['label'] ?? '' }}</th><td>{{ $sp['value'] ?? '' }}</td></tr>@endforeach</table>@break
                                @case('requirements')<div class="pb-text">@if ($product->requirements)<h3>Requirements</h3><p>{!! nl2br(e($product->requirements)) !!}</p>@endif @if ($product->compatibility)<h3>Compatibility</h3><p>{!! nl2br(e($product->compatibility)) !!}</p>@endif</div>@break
                                @case('changelog')<div class="pb-text"><p>{!! nl2br(e($product->changelog)) !!}</p></div>@break
                            @endswitch
                        </div>
                    @endforeach
                </div>
                <aside class="vp-side" data-anim="fade-left">
                    <div class="vp-side-card">
                        @if ($images)<img src="{{ media($images[0]) }}" alt="" loading="lazy">@endif
                        <h3>{{ $product->name }}</h3>
                        <div class="st-price-big" data-price>@include('shop.partials.price', ['product' => $product, 'variant' => $first])</div>
                        @if ($inStock)<a href="#buy" class="pb-btn pb-btn-primary vp-full"><i class="fa-solid fa-cart-plus"></i> <span>Get it now</span></a>@endif
                        <ul class="vp-side-list">
                            <li><i class="fa-solid fa-lock"></i> Secure checkout</li>
                            <li><i class="fa-solid {{ $digital ? 'fa-download' : 'fa-truck-fast' }}"></i> {{ $digital ? 'Download links by email' : 'Delivery updates by email' }}</li>
                            <li><i class="fa-regular fa-envelope"></i> <a href="/contact">Questions? Ask us</a></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </section>
    @endif

    {{-- ============ HOW IT WORKS ============ --}}
    <section class="pb-section sc-dark bg-tech2 vp-section" style="--pt:90px;--pb:150px">
        <div class="pb-fx vb-waves" aria-hidden="true">@for ($i = 1; $i <= 3; $i++)<svg class="w{{ $i }}" viewBox="0 0 2880 120" preserveAspectRatio="none"><path d="M0,60 C240,110 480,10 720,60 C960,110 1200,10 1440,60 C1680,110 1920,10 2160,60 C2400,110 2640,10 2880,60 L2880,120 L0,120 Z"/></svg>@endfor</div>
        <div class="pb-container">
            <div class="vp-head ta-center" data-anim="fade-up"><div class="pb-heading"><p class="pb-eyebrow">How it works</p><h2 class="pb-h size-xl">{{ $digital ? 'From checkout to download' : 'From order to your door' }}</h2></div></div>
            <ol data-inview class="pb-steps l-horizontal" style="--n:3" data-anim="fade-up" data-stagger="120">
                @foreach ($steps as $i => $st)
                    <li class="pb-item pb-step"><span class="pb-step-n c-{{ ['orange', 'blue', 'green'][$i] }}"><i class="{{ $st[0] }}"></i></span><div><h3>{{ $st[1] }}</h3><p>{{ $st[2] }}</p></div></li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============ FAQ ============ --}}
    @if (! empty($product->faqs))
        <section class="pb-section sc-dark vp-section" style="--pt:90px;--pb:90px">
            <div class="pb-fx vb-stars" aria-hidden="true">@for ($i = 0; $i < 14; $i++)<i style="--x:{{ ($i * 37 + 11) % 100 }}%;--y:{{ ($i * 53 + 7) % 100 }}%;--d:{{ ($i % 6) * .55 }}s;--c:{{ $i % 5 }}"></i>@endfor</div>
            <div class="pb-container vp-faq">
                <div class="vp-head" data-anim="fade-up"><div class="pb-heading"><p class="pb-eyebrow">FAQ</p><h2 class="pb-h size-xl">Questions before you buy</h2></div><p class="vp-muted">Something else? <a href="/contact">Send us a message</a>.</p></div>
                <div class="pb-faq" data-accordion data-anim="fade-left">@foreach ($product->faqs as $f)<div class="pb-faq-item"><button type="button" class="pb-faq-q" aria-expanded="false">{{ $f['q'] ?? '' }}<i class="fa-solid fa-plus"></i></button><div class="pb-faq-a pb-text" hidden><p>{!! nl2br(e($f['a'] ?? '')) !!}</p></div></div>@endforeach</div>
            </div>
        </section>
    @endif

    {{-- ============ FINAL CTA ============ --}}
    @if ($inStock)
        <section class="pb-section sc-dark bg-vivid vp-cta" style="--pt:100px;--pb:100px">
            <div class="pb-fx vb-confetti" aria-hidden="true">@for ($i = 0; $i < 22; $i++)<i style="--x:{{ ($i * 41 + 3) % 100 }}%;--d:{{ ($i * 0.7) % 9 }}s;--t:{{ 9 + ($i % 5) * 2 }}s;--c:{{ $i % 5 }};--r:{{ ($i * 47) % 360 }}deg"></i>@endfor</div>
            <div class="pb-container ta-center" data-anim="zoom-in">
                <h2 class="vp-cta-title">Ready to get {{ $product->name }}?</h2>
                <div class="vp-cta-price" data-price>@include('shop.partials.price', ['product' => $product, 'variant' => $first])</div>
                <div class="pb-btns l vp-cta-btns">
                    <a href="#buy" class="pb-btn pb-btn-primary"><i class="fa-solid fa-cart-plus"></i> <span>{{ $variants ? 'Choose an option' : 'Buy now' }}</span></a>
                    <a href="/contact" class="pb-btn pb-btn-ghost"><i class="fa-regular fa-envelope"></i> Ask a question</a>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ RELATED ============ --}}
    @if ($related->isNotEmpty())
        <section class="pb-section sc-dark vp-section" style="--pt:90px;--pb:110px">
            <div class="pb-container">
                <div class="vp-head ta-center" data-anim="fade-up"><div class="pb-heading"><p class="pb-eyebrow">More from the shop</p><h2 class="pb-h size-xl">You may also like</h2></div></div>
                <div class="st-grid" data-anim="fade-up" data-stagger="80">@foreach ($related as $rp)@include('shop.partials.card', ['product' => $rp])@endforeach</div>
            </div>
        </section>
    @endif

    {{-- Sticky buy bar: appears when the main buy box is off screen --}}
    @if ($inStock)
        <div class="vp-sticky" data-sticky-buy aria-hidden="true">
            <div class="vp-sticky-in">
                @if ($images)<img src="{{ media($images[0]) }}" alt="">@endif
                <strong>{{ $product->name }}</strong>
                <span data-price>@include('shop.partials.price', ['product' => $product, 'variant' => $first])</span>
                <a href="#buy" class="pb-btn pb-btn-primary" tabindex="-1"><i class="fa-solid fa-cart-plus"></i> <span>Buy</span></a>
            </div>
        </div>
    @endif
@endsection
