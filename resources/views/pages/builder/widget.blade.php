@php($d = $widget['data'])
<div{!! \App\PageBuilder\Attr::element($widget['settings'], 'pb-w pb-w-'.$widget['type'], $editor ? $widget['id'] : null) !!}>
    @if ($widget['type'] === 'row')
        <div class="pb-row gap-{{ $d['gap'] }} va-{{ $d['valign'] }}">
            @foreach ($widget['columns'] ?? [] as $column)
                @include('pages.builder.column', ['column' => $column])
            @endforeach
        </div>
    @else
        @include('pages.widgets.'.$widget['type'], ['d' => $d, 'wid' => $widget['id']])
    @endif
</div>
