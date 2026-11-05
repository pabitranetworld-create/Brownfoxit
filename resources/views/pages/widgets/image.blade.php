@if ($d['image'])
    <figure class="pb-figure r-{{ $d['radius'] }} {{ $d['shadow'] ? 'shadow' : '' }}" @if ($d['max_width']) style="max-width:{{ (int) $d['max_width'] }}px" @endif>
        @if ($d['url'])<a href="{{ link_to_url($d['url']) }}">@endif
        @include('pages.widgets._image', ['src' => $d['image'], 'alt' => $d['alt'], 'effect' => $d['effect']])
        @if ($d['url'])</a>@endif
        @if ($d['caption'])<figcaption>{{ $d['caption'] }}</figcaption>@endif
    </figure>
@elseif (! empty($editor))
    <div class="pb-placeholder"><i class="fa-regular fa-image"></i> ছবি বেছে নিন</div>
@endif
