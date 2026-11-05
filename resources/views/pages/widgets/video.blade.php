@php($v = $d['url'] ? \App\PageBuilder\Attr::videoEmbed($d['url']) : null)
@if ($v)
    @php($poster = $d['poster'] ? media($d['poster']) : ($v['thumb'] ?? null))
    <div class="pb-video" style="aspect-ratio:{{ $d['ratio'] }}">
        @if ($d['mode'] === 'inline')
            @if ($v['type'] === 'iframe')
                <iframe src="{{ $v['src'] }}" title="{{ $d['title'] }}" loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @else
                <video src="{{ $v['src'] }}" @if ($poster) poster="{{ $poster }}" @endif controls playsinline preload="metadata"></video>
            @endif
        @else
            <button type="button" class="pb-video-cover" data-video-popup="{{ $v['type'] === 'iframe' ? $v['src'].'&autoplay=1' : $v['src'] }}" data-video-type="{{ $v['type'] }}" aria-label="Play: {{ $d['title'] }}">
                @if ($poster)<img src="{{ $poster }}" alt="" loading="lazy">@endif
                <span class="pb-play"><i class="fa-solid fa-play"></i></span>
            </button>
        @endif
    </div>
@elseif (! empty($editor))
    <div class="pb-placeholder"><i class="fa-solid fa-circle-play"></i> ভিডিওর লিংক দিন</div>
@endif
