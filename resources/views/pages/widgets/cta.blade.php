<div class="pb-cta st-{{ $d['style'] }} {{ $d['image'] ? 'has-img' : '' }}">
    <div class="pb-cta-bg" aria-hidden="true"></div>
    <div class="pb-cta-text">
        <h2 data-words>{{ $d['title'] }}</h2>
        @if ($d['text'])<p>{!! nl2br(e($d['text'])) !!}</p>@endif
        @include('pages.widgets._buttons', ['buttons' => $d['buttons'], 'size' => 'l'])
    </div>
    @if ($d['image'])
        <div class="pb-cta-img">@include('pages.widgets._image', ['src' => $d['image'], 'alt' => '', 'effect' => 'float'])</div>
    @endif
</div>
