@php
    $c = $column['settings'];
    $style = [];
    foreach (['w' => 'w', 'w_tablet' => 'wt', 'w_mobile' => 'wm'] as $k => $v) {
        $style[] = '--'.$v.':'.(float) $c[$k].'%';
        $style[] = '--'.$v.'f:'.round((float) $c[$k] / 100, 4);
    }
    if ($c['pad']) { $style[] = '--cpad:'.(int) $c['pad'].'px'; }
@endphp
<div{!! \App\PageBuilder\Attr::element($c, 'pb-col', $editor ? $column['id'] : null, ['box-'.$c['box']], $style) !!}>
    @foreach ($column['widgets'] as $widget)
        @include('pages.builder.widget', ['widget' => $widget])
    @endforeach
    @if ($editor && empty($column['widgets']))
        <div class="pb-empty-col">+ এখানে widget যোগ করুন</div>
    @endif
</div>
