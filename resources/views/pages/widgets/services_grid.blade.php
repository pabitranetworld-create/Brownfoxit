@php
    $items = \App\Models\Service::active()->where('show_in_catalog', true)->ordered()
        ->when(($d['group'] ?? '') === 'main', fn ($q) => $q->whereIn('group', ['ai', 'web', 'software', 'media']))
        ->when(($d['group'] ?? '') === 'none', fn ($q) => $q->where(fn ($w) => $w->whereNull('group')->orWhere('group', '')))
        ->when(! in_array($d['group'] ?? '', ['', 'main', 'none'], true), fn ($q) => $q->where('group', $d['group']))
        ->when(filled($d['exclude'] ?? ''), fn ($q) => $q->where(fn ($w) => $w->whereNull('url')->orWhere('url', '!=', $d['exclude'])))
        ->when((int) $d['count'] > 0, fn ($q) => $q->take((int) $d['count']))
        ->get();
@endphp
@if ($items->isNotEmpty() && filled($d['heading'] ?? ''))
    <div class="pb-projects-head">
        @if (filled($d['eyebrow'] ?? ''))<span class="pb-eyebrow">{{ $d['eyebrow'] }}</span>@endif
        <h2 class="pb-h size-l" data-words>{{ $d['heading'] }}</h2>
    </div>
@endif
@if ($items->isEmpty() && ! empty($editor))<div class="pb-placeholder"><i class="fa-solid fa-laptop-code"></i> এই গ্রুপে এখনো কোনো Service নেই।</div>@endif
<div class="pb-grid cols-{{ $d['columns'] }} pb-iconboxes st-card">
    @foreach ($items as $i => $s)
        <div class="pb-item pb-ibox hv-lift">
            <span class="pb-ibox-ic c-{{ $s->icon_color }}"><i class="{{ $s->icon }}"></i></span>
            <h3>@if ($s->url)<a href="{{ link_to_url($s->url) }}">{{ $s->title }}</a>@else{{ $s->title }}@endif</h3>
            <p>{{ $s->short_description }}</p>
            @if ($s->url)<a class="pb-more" href="{{ link_to_url($s->url) }}" aria-label="{{ $s->title }}">Learn more <i class="fa-solid fa-arrow-right"></i></a>@endif
        </div>
    @endforeach
</div>
