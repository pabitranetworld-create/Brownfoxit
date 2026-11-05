@php($tag = $d['style'] === 'number' ? 'ol' : 'ul')
<{{ $tag }} class="pb-list st-{{ $d['style'] }} cols-{{ $d['columns'] }}">
    @foreach ($d['items'] as $item)
        <li class="pb-item">{{ $item['text'] }}</li>
    @endforeach
</{{ $tag }}>
