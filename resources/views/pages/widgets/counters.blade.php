<div class="pb-grid cols-{{ min(4, max(1, count($d['items']))) }} pb-counters">
    @foreach ($d['items'] as $i => $item)
        <div class="pb-item pb-counter">
            <div class="pb-counter-n c-{{ \App\PageBuilder\Attr::cycle('cycle', $i) }}"><span class="count" data-to="{{ (float) $item['value'] }}">0</span>{{ $item['suffix'] }}</div>
            <p>{{ $item['label'] }}</p>
        </div>
    @endforeach
</div>
