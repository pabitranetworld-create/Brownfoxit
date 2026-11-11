@php($details = array_values(array_filter($d['details'] ?? [], fn ($i) => filled($i['label'] ?? '') && filled($i['value'] ?? ''))))
<div class="cr">
    <div class="cr-doc pb-item" @if ($d['image']) data-lightbox @endif>
        <div class="cr-stack" aria-hidden="true"><span></span><span></span></div>
        @if ($d['image'])
            <a href="{{ media($d['image']) }}" class="cr-paper" data-caption="{{ $d['alt'] }}">
                <img src="{{ media($d['image']) }}" alt="{{ $d['alt'] }}" loading="lazy">
                <span class="cr-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
            </a>
        @else
            <div class="cr-paper cr-blank" role="img" aria-label="License image will appear here">
                <span class="cr-blank-head"></span>
                @for ($i = 0; $i < 9; $i++)<span class="cr-line" style="width: {{ [92, 80, 88, 70, 95, 60, 85, 75, 50][$i] }}%"></span>@endfor
                <span class="cr-seal"><i class="fa-solid fa-stamp"></i></span>
                @if (! empty($editor))<em>ডান পাশের প্যানেল থেকে লাইসেন্সের ছবি দিন</em>@endif
            </div>
        @endif
        <span class="cr-badge"><i class="fa-solid fa-shield-halved"></i> Verified</span>
    </div>
    <div class="cr-info">
        @if (filled($d['eyebrow'] ?? ''))<span class="si-eyebrow"><i class="fa-solid fa-circle"></i> {{ $d['eyebrow'] }}</span>@endif
        @if (filled($d['heading'] ?? ''))<h2 class="si-title">{{ $d['heading'] }}</h2>@endif
        @if (filled(strip_tags($d['content'] ?? '')))<div class="si-copy">{!! $d['content'] !!}</div>@endif
        @if ($details)
            <dl class="cr-details">
                @foreach ($details as $row)<div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>@endforeach
            </dl>
        @endif
        @if ($d['image'] && filled($d['button'] ?? ''))
            <div data-lightbox><a href="{{ media($d['image']) }}" class="pb-btn pb-btn-ghost cr-view" data-caption="{{ $d['alt'] }}"><i class="fa-solid fa-up-right-and-down-left-from-center"></i> {{ $d['button'] }}</a></div>
        @endif
    </div>
</div>
