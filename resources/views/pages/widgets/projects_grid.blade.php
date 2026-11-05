@php
    $items = \App\Models\Project::active()->ordered()
        ->when($d['category'], fn ($q) => $q->where('category', 'like', '%'.$d['category'].'%'))
        ->when((int) $d['count'] > 0, fn ($q) => $q->take((int) $d['count']))->get();
    $cats = $items->flatMap(fn ($p) => array_map('trim', explode(',', (string) $p->category)))->filter()->unique()->values();
@endphp
<div class="pb-projects" data-filter-group>
    @if ($d['filter'] && $cats->count() > 1)
        <div class="pb-filter" role="toolbar" aria-label="Filter projects">
            <button type="button" class="on" data-filter="*">All</button>
            @foreach ($cats as $cat)
                <button type="button" data-filter="{{ \Illuminate\Support\Str::slug($cat) }}">{{ $cat }}</button>
            @endforeach
        </div>
    @endif
    <div class="pb-grid cols-{{ $d['columns'] }}" data-filter-items>
        @foreach ($items as $p)
            <article class="pb-item pb-project" data-cats="{{ collect(explode(',', (string) $p->category))->map(fn ($c) => \Illuminate\Support\Str::slug(trim($c)))->implode(' ') }}">
                <a href="{{ $p->url ?: '#' }}" @if ($p->url) target="_blank" rel="noopener" @endif class="pb-project-link">
                    <div class="pb-img fx-zoom"><img src="{{ media($p->image) }}" alt="{{ $p->title }}" loading="lazy"></div>
                    <div class="pb-project-meta"><span>{{ $p->category }}</span><h3>{{ $p->title }}</h3></div>
                </a>
            </article>
        @endforeach
    </div>
</div>
