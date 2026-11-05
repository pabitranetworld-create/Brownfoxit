<div class="pb-grid cols-{{ $d['columns'] }} pb-iconboxes st-{{ $d['style'] }} al-{{ $d['align'] }}">
    @foreach ($d['items'] as $i => $item)
        <div class="pb-item pb-ibox">
            <span class="pb-ibox-ic c-{{ \App\PageBuilder\Attr::cycle($d['icon_color'], $i) }}"><i class="{{ $item['icon'] ?: 'fa-solid fa-circle' }}"></i></span>
            <h3>{{ $item['title'] }}</h3>
            @if ($item['text'])<p>{!! nl2br(e($item['text'])) !!}</p>@endif
            @if ($item['url'])<a class="pb-more" href="{{ link_to_url($item['url']) }}" @if (preg_match('#^https?://#', $item['url']) && ! str_starts_with($item['url'], url('/'))) target="_blank" rel="noopener" @endif>{{ ($item['link_text'] ?? '') ?: 'Learn more' }} <i class="fa-solid fa-arrow-right"></i></a>@endif
        </div>
    @endforeach
</div>
