@php($items = array_values(array_filter($d['items'] ?? [], fn ($i) => filled($i['title'] ?? ''))))
@php($stats = array_values(array_filter($d['stats'] ?? [], fn ($i) => filled($i['value'] ?? ''))))
@php($tones = ['t-orange', 't-blue', 't-green', 't-purple', 't-pink', 't-cyan'])
<div class="si">
    <div class="si-text">
        @if (filled($d['eyebrow'] ?? ''))<span class="si-eyebrow"><i class="fa-solid fa-circle"></i> {{ $d['eyebrow'] }}</span>@endif
        @if (filled($d['heading'] ?? ''))<h2 class="si-title">{{ $d['heading'] }}</h2>@endif
        <div class="si-copy">{!! $d['content'] ?? '' !!}</div>
        @if ($stats)
            <div class="si-stats">
                @foreach ($stats as $st)<div class="si-stat"><strong>{{ $st['value'] }}</strong><span>{{ $st['label'] }}</span></div>@endforeach
            </div>
        @endif
    </div>
    @if ($items)
        <div class="si-bento n{{ count($items) }}">
            @foreach ($items as $k => $it)
                @php($tag = filled($it['url'] ?? '') ? 'a' : 'div')
                <{{ $tag }} class="si-card pb-item {{ $tones[$k % count($tones)] }} {{ $k === 0 ? 'feat' : '' }}" @if ($tag === 'a') href="{{ link_to_url($it['url']) }}" @endif>
                    <span class="si-glow" aria-hidden="true"></span>
                    <span class="si-ic"><i class="{{ $it['icon'] ?: 'fa-solid fa-star' }}"></i></span>
                    <span class="si-num" aria-hidden="true">{{ str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $it['title'] }}</h3>
                    @if (filled($it['text'] ?? ''))<p>{{ $it['text'] }}</p>@endif
                    @if ($tag === 'a')<span class="si-go" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>@endif
                </{{ $tag }}>
            @endforeach
        </div>
    @endif
</div>
