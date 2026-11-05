<ol data-inview class="pb-steps l-{{ $d['layout'] }}" style="--n:{{ max(1, count($d['items'])) }}">
    @foreach ($d['items'] as $i => $item)
        <li class="pb-item pb-step">
            <span class="pb-step-n c-{{ \App\PageBuilder\Attr::cycle('cycle', $i) }}">@if ($item['icon'])<i class="{{ $item['icon'] }}"></i>@else{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}@endif</span>
            <div><h3>{{ $item['title'] }}</h3>@if ($item['text'])<p>{!! nl2br(e($item['text'])) !!}</p>@endif</div>
        </li>
    @endforeach
</ol>
