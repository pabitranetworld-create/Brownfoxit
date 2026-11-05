@php($items = \App\Models\Service::active()->ordered()->when((int) $d['count'] > 0, fn ($q) => $q->take((int) $d['count']))->get())
<div class="pb-grid cols-{{ $d['columns'] }} pb-iconboxes st-card">
    @foreach ($items as $i => $s)
        <div class="pb-item pb-ibox hv-lift">
            <span class="pb-ibox-ic c-{{ $s->icon_color }}"><i class="{{ $s->icon }}"></i></span>
            <h3>{{ $s->title }}</h3>
            <p>{{ $s->short_description }}</p>
        </div>
    @endforeach
</div>
