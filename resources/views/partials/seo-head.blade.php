{{-- The only place that prints title / description / canonical / robots / social / JSON-LD tags. --}}
@php
    seo()->set([
        'title' => trim($__env->yieldContent('title')),
        'description' => trim($__env->yieldContent('meta_description')),
        'robots' => trim($__env->yieldContent('robots')),
        'canonical' => trim($__env->yieldContent('canonical')),
        'image' => trim($__env->yieldContent('og_image')),
        'type' => trim($__env->yieldContent('og_type')),
    ]);
    $S = seo()->resolve();
@endphp
    <title>{{ $S['title'] }}</title>
    @if ($S['description'])<meta name="description" content="{{ $S['description'] }}">@endif
    <meta name="robots" content="{{ $S['robots'] }}">
    <link rel="canonical" href="{{ $S['canonical'] }}">
    <meta property="og:locale" content="{{ $S['locale'] }}">
    <meta property="og:site_name" content="{{ \App\Seo\Seo::siteName() }}">
    <meta property="og:type" content="{{ $S['og_type'] }}">
    <meta property="og:title" content="{{ $S['og_title'] }}">
    @if ($S['og_description'])<meta property="og:description" content="{{ $S['og_description'] }}">@endif
    <meta property="og:url" content="{{ $S['canonical'] }}">
    @if ($S['og_image'])
    <meta property="og:image" content="{{ $S['og_image'] }}">
    <meta property="og:image:alt" content="{{ $S['og_image_alt'] }}">
    @endif
    @if ($S['og_type'] === 'article' && $S['published'])
    <meta property="article:published_time" content="{{ $S['published']->toAtomString() }}">
    @if ($S['modified'])<meta property="article:modified_time" content="{{ $S['modified']->toAtomString() }}">@endif
    @endif
    <meta name="twitter:card" content="{{ $S['twitter_card'] }}">
    @if ($S['twitter_site'])<meta name="twitter:site" content="{{ '@'.$S['twitter_site'] }}">@endif
    <meta name="twitter:title" content="{{ $S['og_title'] }}">
    @if ($S['og_description'])<meta name="twitter:description" content="{{ $S['og_description'] }}">@endif
    @if ($S['og_image'])<meta name="twitter:image" content="{{ $S['og_image'] }}"><meta name="twitter:image:alt" content="{{ $S['og_image_alt'] }}">@endif
    <script type="application/ld+json">{!! $S['jsonld'] !!}</script>
    @if ($v = setting('seo_integrations.google_verification'))<meta name="google-site-verification" content="{{ $v }}">@endif
    @if ($v = setting('seo_integrations.bing_verification'))<meta name="msvalidate.01" content="{{ $v }}">@endif
    @include('partials.analytics')
