@php
    $title = e($d['title']);
    $words = array_values(array_filter(array_map('trim', explode(',', (string) ($d['rotate_words'] ?? '')))));
    if ($d['highlight'] && str_contains($d['title'], $d['highlight'])) {
        $span = $words
            ? '<span class="grad-text tx-rotate" data-rotate="'.e(json_encode(array_merge([$d['highlight']], $words), JSON_UNESCAPED_UNICODE)).'">'.e($d['highlight']).'</span>'
            : '<span class="grad-text tx-scramble">'.e($d['highlight']).'</span>';
        $title = str_replace(e($d['highlight']), $span, $title);
    }
    $visual = ($d['visual'] ?? 'none') !== 'none' && ! $d['image'] ? $d['visual'] : null;
    if ($visual === 'auto') {
        $visual = \App\PageBuilder\Visuals::guess($d['title'], $d['eyebrow'] ?? '', request()->path());
    }
    $bgWords = $words ?: array_slice(array_filter(preg_split('/\s+/', strtoupper($d['highlight'] ?: $d['title']))), 0, 3);
@endphp
<div class="pb-hero l-{{ $visual && $d['layout'] === 'center' ? 'split' : $d['layout'] }} {{ $d['image'] || $visual ? 'has-img' : 'no-img' }} {{ $visual ? 'has-visual' : '' }}">
    @if ($visual)
        {{-- giant outline words running behind the hero --}}
        <div class="tx-bigrun" aria-hidden="true"><div>@for ($c = 0; $c < 2; $c++)<span>@foreach ($bgWords as $w){{ strtoupper($w) }} <b>✦</b> @endforeach</span>@endfor</div></div>
    @endif
    <div class="pb-hero-text">
        @if ($d['eyebrow'])<p class="pb-eyebrow" data-anim="fade-up"><span class="tx-type" data-type="{{ $d['eyebrow'] }}">{{ $d['eyebrow'] }}</span></p>@endif
        <h1 class="pb-hero-title" @if ($d['title_effect'] === 'words') data-words @endif>{!! $title !!}</h1>
        @if ($d['text'])<p class="pb-hero-desc" data-anim="fade-up" data-anim-delay="250">{!! nl2br(e($d['text'])) !!}</p>@endif
        <div data-anim="fade-up" data-anim-delay="400">@include('pages.widgets._buttons', ['buttons' => $d['buttons'], 'size' => 'l'])</div>
    </div>
    @if ($d['image'])
        <div class="pb-hero-media" data-anim="zoom-in" data-anim-delay="200">
            <div class="pb-hero-glow" aria-hidden="true"></div>
            @include('pages.widgets._image', ['src' => $d['image'], 'alt' => '', 'effect' => $d['image_effect']])
        </div>
    @elseif ($visual)
        <div class="pb-hero-media tv-wrap" data-anim="zoom-in" data-anim-delay="200" aria-hidden="true">
            @include('pages.visuals.scene', ['scene' => $visual])
        </div>
    @endif
</div>
