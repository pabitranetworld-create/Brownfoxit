@php
    $pageTitle = $project->title;
    $pageSubtitle = $project->summary;
    $crumbs = [['label' => 'Portfolio', 'url' => route('portfolio.index')]];
    $gallery = array_values(array_filter($project->gallery ?? [], fn ($g) => ! empty(is_array($g) ? ($g['image'] ?? null) : $g)));
    $facts = array_filter(['Client' => $project->client, 'Year' => $project->year, 'Services' => $project->services, 'Category' => $project->category]);
@endphp
@extends('layouts.store')
@php(seo()->subject($project))
@section('title', $project->title)

@section('store')
    <article class="st-case">
        @if ($project->image)<div class="st-case-hero pb-img" data-reveal data-anim="fade-up"><img src="{{ media($project->image) }}" alt="{{ $project->title }}"></div>@endif

        <div class="st-case-body">
            <div class="st-case-main">
                @if ($project->description)<section data-anim="fade-up"><h2 class="st-h2">Overview</h2><div class="pb-text">{!! \App\PageBuilder\HtmlSanitizer::clean($project->description) !!}</div></section>@endif
                @foreach (['challenge' => 'The challenge', 'solution' => 'What we did', 'result' => 'The result'] as $f => $label)
                    @if (filled($project->{$f}))<section data-anim="fade-up"><h2 class="st-h2">{{ $label }}</h2><div class="pb-text">{!! \App\PageBuilder\HtmlSanitizer::clean($project->{$f}) !!}</div></section>@endif
                @endforeach
                @if ($project->video_url)<section data-anim="fade-up">@include('pages.widgets.video', ['d' => ['url' => $project->video_url, 'poster' => $project->image ?? '', 'title' => $project->title, 'mode' => 'popup', 'ratio' => '16/9'], 'wid' => 'cv'])</section>@endif
            </div>
            <aside class="st-side st-case-side" data-anim="fade-left">
                @if ($facts)<dl class="st-totals">@foreach ($facts as $k => $v)<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl>@endif
                @if ($project->tags)<div class="st-tags">@foreach (array_filter(array_map('trim', explode(',', $project->tags))) as $t)<span>{{ $t }}</span>@endforeach</div>@endif
                @if ($project->url)<a class="pb-btn pb-btn-primary st-wide" href="{{ $project->url }}" target="_blank" rel="noopener">View live project <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
                <a class="pb-btn pb-btn-ghost st-wide" href="{{ url('/start-a-project') }}">Start a similar project</a>
            </aside>
        </div>

        @if ($gallery)
            <section class="st-block"><h2 class="st-h2" data-anim="fade-up">Gallery</h2>
                <div class="pb-gallery pb-grid cols-3" data-lightbox data-stagger="70">
                    @foreach ($gallery as $g)
                        @php($src = is_array($g) ? $g['image'] : $g)
                        @php($cap = is_array($g) ? ($g['caption'] ?? '') : '')
                        <figure class="pb-item pb-gal-item" style="aspect-ratio:4/3"><a href="{{ media($src) }}" data-lb data-caption="{{ $cap }}"><img src="{{ media($src) }}" alt="{{ (is_array($g) ? ($g['alt'] ?? '') : '') ?: ($cap ?: $project->title) }}" loading="lazy"><span class="pb-gal-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span></a>@if ($cap)<figcaption>{{ $cap }}</figcaption>@endif</figure>
                    @endforeach
                </div>
            </section>
        @endif

        <nav class="st-prevnext" aria-label="More projects">
            @if ($prev)<a href="{{ url('/portfolio/'.$prev->slug) }}"><small>Previous</small><strong><i class="fa-solid fa-arrow-left"></i> {{ $prev->title }}</strong></a>@else<span></span>@endif
            @if ($next)<a href="{{ url('/portfolio/'.$next->slug) }}" class="r"><small>Next</small><strong>{{ $next->title }} <i class="fa-solid fa-arrow-right"></i></strong></a>@endif
        </nav>

        @if ($related->isNotEmpty())
            <section class="st-block"><h2 class="st-h2" data-anim="fade-up">Related projects</h2>
                <div class="pb-grid cols-3" data-stagger="80">@foreach ($related as $p)<article class="pb-item pb-project"><a href="{{ $p->link() }}" class="pb-project-link"><div class="pb-img fx-zoom">@if ($p->image)<img src="{{ media($p->image) }}" alt="{{ $p->title }}" loading="lazy">@endif</div><div class="pb-project-meta"><span>{{ $p->category }}</span><h3>{{ $p->title }}</h3></div></a></article>@endforeach</div>
            </section>
        @endif
    </article>
@endsection
