@php
    $title = e($d['title']);
    if ($d['highlight'] && str_contains($d['title'], $d['highlight'])) {
        $title = str_replace(e($d['highlight']), '<span class="grad-text">'.e($d['highlight']).'</span>', $title);
    }
@endphp
<div class="pb-hero l-{{ $d['layout'] }} {{ $d['image'] ? 'has-img' : 'no-img' }}">
    <div class="pb-hero-text">
        @if ($d['eyebrow'])<p class="pb-eyebrow" data-anim="fade-up">{{ $d['eyebrow'] }}</p>@endif
        <h1 class="pb-hero-title" @if ($d['title_effect'] === 'words') data-words @endif>{!! $title !!}</h1>
        @if ($d['text'])<p class="pb-hero-desc" data-anim="fade-up" data-anim-delay="250">{!! nl2br(e($d['text'])) !!}</p>@endif
        <div data-anim="fade-up" data-anim-delay="400">@include('pages.widgets._buttons', ['buttons' => $d['buttons'], 'size' => 'l'])</div>
    </div>
    @if ($d['image'])
        <div class="pb-hero-media" data-anim="zoom-in" data-anim-delay="200">
            <div class="pb-hero-glow" aria-hidden="true"></div>
            @include('pages.widgets._image', ['src' => $d['image'], 'alt' => '', 'effect' => $d['image_effect']])
        </div>
    @endif
</div>
