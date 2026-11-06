@php
    $pageTitle = $product->name;
    $pageSubtitle = null;
    $crumbs = array_values(array_filter([['label' => 'Shop', 'url' => route('shop.index')], $product->category ? ['label' => $product->category->name, 'url' => $product->category->link()] : null]));
    $images = array_values(array_filter(array_merge([$product->image], array_map(fn ($g) => is_array($g) ? ($g['image'] ?? null) : $g, $product->gallery ?? []))));
    $variants = $product->variants ?? [];
    $first = $variants[0]['key'] ?? null;
    $tabs = array_filter([
        'description' => filled($product->description) ? 'Description' : null,
        'specs' => ! empty($product->specifications) ? 'Specifications' : null,
        'requirements' => filled($product->requirements) || filled($product->compatibility) ? 'Requirements' : null,
        'changelog' => filled($product->changelog) ? 'Changelog' : null,
        'faq' => ! empty($product->faqs) ? 'FAQ' : null,
    ]);
@endphp
@extends('layouts.store')

@section('title', ($product->seo_title ?: $product->name).' · '.setting('site.name'))
@section('meta_description', $product->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 155))
@if ($product->status !== 'published')@section('robots', 'noindex, nofollow')@endif
@push('head')
    @if ($product->og_image || $product->image)<meta property="og:image" content="{{ media($product->og_image ?: $product->image) }}">@endif
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->name, 'sku' => $product->sku,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 300),
        'image' => $images ? array_map(fn ($i) => media($i), $images) : null,
        'offers' => ['@type' => 'Offer', 'price' => number_format($product->fromPrice(), 2, '.', ''), 'priceCurrency' => store('currency', 'BDT'), 'availability' => $product->inStock($first) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => $product->link()],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('store')
    @if ($product->status !== 'published')<div class="st-alert warn"><i class="fa-solid fa-eye-slash"></i> Preview — this product is <strong>{{ $product->status }}</strong> and not visible to customers.</div>@endif
    <div class="st-product">
        <div class="st-gallery" data-anim="fade-right" data-gallery>
            <div class="st-gallery-main pb-img">
                @if ($images)<img src="{{ media($images[0]) }}" alt="{{ $product->name }}" data-main>@else<span class="st-noimg big"><i class="fa-solid fa-box-open"></i></span>@endif
            </div>
            @if (count($images) > 1)
                <div class="st-thumbs">
                    @foreach ($images as $i => $img)<button type="button" @class(['on' => $i === 0]) data-src="{{ media($img) }}" aria-label="Image {{ $i + 1 }}"><img src="{{ media($img) }}" alt="" loading="lazy"></button>@endforeach
                </div>
            @endif
        </div>

        <div class="st-summary" data-anim="fade-left">
            @if ($product->category)<a class="st-card-cat" href="{{ $product->category->link() }}">{{ $product->category->name }}</a>@endif
            <h1 class="st-title">{{ $product->name }}</h1>
            <div class="st-price-big" data-price>@include('shop.partials.price', ['product' => $product, 'variant' => $first])</div>
            @if ($product->short_description)<div class="pb-text st-short">{!! \App\PageBuilder\HtmlSanitizer::clean($product->short_description) !!}</div>@endif

            <form method="post" action="{{ route('cart.add') }}" class="st-buy" data-add-cart data-full>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                @if ($variants)
                    <fieldset class="st-variants"><legend>Choose an option</legend>
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
                    @unless ($product->isDigital())
                        <label class="st-qty"><span class="sr-only">Quantity</span><button type="button" data-qty="-1" aria-label="Less">−</button><input type="number" name="qty" value="1" min="1" max="99" inputmode="numeric"><button type="button" data-qty="1" aria-label="More">+</button></label>
                    @endunless
                    @if ($product->inStock($first) || $variants)
                        <button type="submit" class="pb-btn pb-btn-primary st-add"><i class="fa-solid fa-cart-plus"></i> <span>Add to cart</span></button>
                        <button type="submit" name="buy_now" value="1" class="pb-btn pb-btn-ghost st-buynow">Buy now</button>
                    @else
                        <span class="st-out big">Out of stock</span>
                    @endif
                </div>
                <p class="st-buy-msg" role="status" aria-live="polite"></p>
            </form>

            <ul class="st-meta">
                <li><i class="fa-solid {{ $product->isDigital() ? 'fa-download' : 'fa-truck-fast' }}"></i> {{ $product->isDigital() ? 'Instant download after confirmed payment' : 'Shipped to your address' }}</li>
                @if (! is_null($product->stockLeft($first)))<li><i class="fa-solid fa-boxes-stacked"></i> {{ $product->stockLeft($first) }} in stock</li>@endif
                @if ($product->version)<li><i class="fa-solid fa-code-branch"></i> Version {{ $product->version }}</li>@endif
                @if ($product->sku)<li><i class="fa-solid fa-barcode"></i> SKU {{ $product->sku }}</li>@endif
            </ul>
            @if ($product->demo_url || $product->documentation_url)
                <div class="pb-btns st-links">
                    @if ($product->demo_url)<a class="pb-btn pb-btn-link" href="{{ $product->demo_url }}" target="_blank" rel="noopener"><span>Live demo</span> <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
                    @if ($product->documentation_url)<a class="pb-btn pb-btn-link" href="{{ $product->documentation_url }}" target="_blank" rel="noopener"><span>Documentation</span> <i class="fa-solid fa-book"></i></a>@endif
                </div>
            @endif
        </div>
    </div>

    @if (! empty($product->features))
        <div class="st-block" data-anim="fade-up">
            <h2 class="st-h2">Features</h2>
            <ul class="pb-list st-check cols-2" data-stagger="60">@foreach ($product->features as $f)<li class="pb-item">{{ is_array($f) ? ($f['text'] ?? '') : $f }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($product->video_url)
        <div class="st-block" data-anim="fade-up">@include('pages.widgets.video', ['d' => ['url' => $product->video_url, 'poster' => $product->image ?? '', 'title' => $product->name, 'mode' => 'popup', 'ratio' => '16/9'], 'wid' => 'pv'])</div>
    @endif

    @if ($tabs)
        <div class="st-block pb-tabs" data-tabs data-anim="fade-up">
            <div class="pb-tab-list" role="tablist">
                @foreach ($tabs as $k => $label)<button type="button" role="tab" @class(['pb-tab', 'on' => $loop->first]) data-tab="{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>@endforeach
                <span class="pb-tab-ink"></span>
            </div>
            @foreach ($tabs as $k => $label)
                <div class="pb-tab-panel" role="tabpanel" data-panel="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                    @switch($k)
                        @case('description')<div class="pb-text">{!! \App\PageBuilder\HtmlSanitizer::clean((string) $product->description) !!}</div>@break
                        @case('specs')<table class="st-specs">@foreach ($product->specifications as $sp)<tr><th>{{ $sp['label'] ?? '' }}</th><td>{{ $sp['value'] ?? '' }}</td></tr>@endforeach</table>@break
                        @case('requirements')<div class="pb-text">@if ($product->requirements)<h3>Requirements</h3><p>{!! nl2br(e($product->requirements)) !!}</p>@endif @if ($product->compatibility)<h3>Compatibility</h3><p>{!! nl2br(e($product->compatibility)) !!}</p>@endif</div>@break
                        @case('changelog')<div class="pb-text"><p>{!! nl2br(e($product->changelog)) !!}</p></div>@break
                        @case('faq')<div class="pb-faq" data-accordion>@foreach ($product->faqs as $f)<div class="pb-faq-item"><button type="button" class="pb-faq-q" aria-expanded="false">{{ $f['q'] ?? '' }}<i class="fa-solid fa-plus"></i></button><div class="pb-faq-a pb-text" hidden><p>{!! nl2br(e($f['a'] ?? '')) !!}</p></div></div>@endforeach</div>@break
                    @endswitch
                </div>
            @endforeach
        </div>
    @endif

    @if ($related->isNotEmpty())
        <div class="st-block">
            <h2 class="st-h2" data-anim="fade-up">You may also like</h2>
            <div class="st-grid" data-stagger="80">@foreach ($related as $product)@include('shop.partials.card')@endforeach</div>
        </div>
    @endif
@endsection
