@php
    $pageTitle = setting('portfolio_page.title') ?: 'Portfolio';
    $pageSubtitle = setting('portfolio_page.subtitle') ?: 'A selection of websites, software, designs and videos we have worked on.';
@endphp
@extends('layouts.store')
@section('title', $pageTitle.' · '.setting('site.name'))
@section('meta_description', setting('portfolio_page.meta_description') ?: $pageSubtitle)

@section('store')
    @if ($projects->isEmpty())
        <div class="st-empty"><i class="fa-solid fa-briefcase"></i><h2>Projects are being added</h2><p>Have a look at our services in the meantime.</p><a class="st-btn" href="{{ url('/services') }}">Our services</a></div>
    @else
        <div class="pb-projects" data-filter-group>
            @if ($categories->count() > 1)
                <div class="pb-filter" role="toolbar" aria-label="Filter projects" data-anim="fade-up">
                    <button type="button" class="on" data-filter="*">All</button>
                    @foreach ($categories as $cat)<button type="button" data-filter="{{ \Illuminate\Support\Str::slug($cat) }}">{{ $cat }}</button>@endforeach
                </div>
            @endif
            <div class="pb-grid cols-3" data-filter-items data-stagger="80">
                @foreach ($projects as $p)
                    <article class="pb-item pb-project" data-cats="{{ collect($p->categories())->map(fn ($c) => \Illuminate\Support\Str::slug($c))->implode(' ') }}">
                        <a href="{{ $p->link() }}" class="pb-project-link">
                            <div class="pb-img fx-zoom">@if ($p->image)<img src="{{ media($p->image) }}" alt="{{ $p->title }}" loading="lazy">@else<span class="st-noimg"><i class="fa-solid fa-image"></i></span>@endif</div>
                            <div class="pb-project-meta"><span>{{ $p->category }}</span><h3>{{ $p->title }}</h3></div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
@endsection
