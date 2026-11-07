@php
    $pageTitle = $category ? $category->name : (store('shop_title') ?: 'Shop');
    $pageSubtitle = $category ? $category->description : (store('shop_subtitle') ?: null);
    $crumbs = $category ? [['label' => 'Shop', 'url' => route('shop.index')]] : [];
@endphp
@extends('layouts.store')

@php(seo()->subject($category ?: 'shop'))
@section('title', ($q !== '' ? 'Search: '.$q.' · ' : '').$pageTitle)
@if (! $category && store('shop_meta_description'))@section('meta_description', store('shop_meta_description'))@endif
@if ($q !== '')@section('robots', 'noindex, follow')@endif

@section('after_header')
    @php($band = array_merge($categories->pluck('name')->all(), ['Secure checkout', 'Instant downloads for digital products', 'Order updates by email']))
    <div class="vp-shopwrap"><div class="vb-marquee st-band vp-shopband" style="--mq-speed: {{ max(25, count($band) * 4) }}s" aria-hidden="true">
        <div class="vb-mq-row">@for ($copy = 0; $copy < 2; $copy++)<ul class="vb-mq-track">@foreach ($band as $i => $b)<li class="vb-mq-item"><span>{{ $b }}</span></li>@endforeach</ul>@endfor</div>
    </div></div>
@endsection

@section('store')
    <div class="st-toolbar">
        <form method="get" action="{{ $category ? $category->link() : route('shop.index') }}" class="st-search" role="search">
            <i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $q }}" placeholder="Search products…" aria-label="Search products">
            @if ($sort !== 'default')<input type="hidden" name="sort" value="{{ $sort }}">@endif
        </form>
        <div class="st-cats" role="navigation" aria-label="Categories">
            <a href="{{ route('shop.index') }}" @class(['on' => ! $category])>All</a>
            @foreach ($categories as $c)<a href="{{ $c->link() }}" @class(['on' => $category?->id === $c->id])>{{ $c->name }}</a>@endforeach
        </div>
        <form method="get" class="st-sort">
            @if ($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
            <select name="sort" onchange="this.form.submit()" aria-label="Sort products">
                @foreach (['default' => 'Recommended', 'newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $k => $label)<option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>@endforeach
            </select>
        </form>
    </div>

    @if ($featured->isNotEmpty())
        <h2 class="st-h2" data-anim="fade-up">Featured products</h2>
        <div class="st-grid" data-stagger="90">@foreach ($featured as $product)@include('shop.partials.card')@endforeach</div>
        <h2 class="st-h2" data-anim="fade-up" style="margin-top:60px">All products</h2>
    @endif

    @if ($products->isEmpty())
        <div class="st-empty" data-anim="fade-up"><i class="fa-solid fa-store"></i>
            <h2>{{ $q !== '' ? 'No products match “'.$q.'”' : 'New products are coming soon' }}</h2>
            <p>{{ $q !== '' ? 'Try another word or browse all products.' : (store('shop_empty_text') ?: 'We are preparing our product catalogue. In the meantime, have a look at our services.') }}</p>
            <a class="st-btn" href="{{ $q !== '' ? route('shop.index') : url('/services') }}">{{ $q !== '' ? 'All products' : 'Our services' }}</a>
        </div>
    @else
        <div class="st-grid" data-stagger="80">@foreach ($products as $product)@include('shop.partials.card')@endforeach</div>
        <div class="st-pages">{{ $products->links('shop.partials.pagination') }}</div>
    @endif
@endsection
